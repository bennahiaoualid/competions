<?php

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Str;
use Database\Seeders\RoleSeeder;
use App\Jobs\Admin\RestoreAdminJob;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Admin\HardDeleteAdminJob;
use App\Models\Monitoring\DeletionRequest;
use App\Jobs\Admin\DeleteAdminCoordinatorJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeletionRecordsFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Admin
     */
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshApplicationWithLocale('en');
        $this->seed(RoleSeeder::class);
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('owner');
        $this->actingAs($this->admin, 'admin');
    }

    public function test_owner_can_view_deletion_records_page()
    {
        $response = $this->get(route('admin.monitoring.deletion-records'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.monitoring.deletion_records');
    }

    public function test_owner_can_hard_delete_user()
    {
        $this->admin->givePermissionTo('hard_delete user');
        $user = User::factory()->create();
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => User::class,
            'deletable_id' => $user->id,
        ]);
        $response = $this->post(route('admin.monitoring.deletion-records.hard-delete'), [
                'deletion_request_id' => $deletionRequest->id,
            ]);
        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_owner_can_restore_soft_deleted_user()
    {
        $this->admin->givePermissionTo('restore user');
        $user = User::factory()->create();
        $user->delete();
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => User::class,
            'deletable_id' => $user->id,
        ]);
        $response = $this->post(route('admin.monitoring.deletion-records.restore'), [
                'deletion_request_id' => $deletionRequest->id,
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_owner_can_hard_delete_admin_and_transfer_ownership()
    {
        Bus::fake();
        $this->admin->givePermissionTo('hard_delete admin');
        $adminToDelete = Admin::factory()->create(['deleted_at' => now()]);
        $transferAdmin = Admin::factory()->create();
        $transferAdmin->givePermissionTo('hard_delete admin');
        $transferAdmin->availability()->update(['ownership_transfer' => true]);
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => $adminToDelete->id,
        ]);
        $response = $this->post(route('admin.monitoring.deletion-records.hard-delete'), [
                'deletion_request_id' => $deletionRequest->id,
                'admin_id' => $transferAdmin->id,
            ]);
        $response->assertRedirect();
        Bus::assertDispatched(DeleteAdminCoordinatorJob::class);
    }

    public function test_owner_can_not_hard_delete_admin_transfer_ownership_admin_not_available()
    {
        Bus::fake();
        $this->admin->givePermissionTo('hard_delete admin');
        $adminToDelete = Admin::factory()->create(['deleted_at' => now()]);
        $transferAdmin = Admin::factory()->create();
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => $adminToDelete->id,
        ]);
        $response = $this->post(route('admin.monitoring.deletion-records.hard-delete'), [
                'deletion_request_id' => $deletionRequest->id,
                'admin_id' => $transferAdmin->id,
            ]);
        $response->assertRedirect();
        Bus::assertNotDispatched(DeleteAdminCoordinatorJob::class);
    }

    public function test_owner_can_restore_soft_deleted_admin_dispatches_job()
    {
        Bus::fake();
        $this->admin->givePermissionTo('restore admin');
        $adminToRestore = Admin::factory()->create(['deleted_at' => now()]);
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => $adminToRestore->id,
        ]);
        $response = $this->post(route('admin.monitoring.deletion-records.restore'), [
                'deletion_request_id' => $deletionRequest->id,
            ]);
        $response->assertRedirect();
        Bus::assertDispatched(RestoreAdminJob::class, function ($job) use ($adminToRestore, $deletionRequest) {
            return $job->getAdmin()->id === $adminToRestore->id
                && $job->getDeletionRequest()->id === $deletionRequest->id
                && $job->getInitiatorAdmin()->id === $this->admin->id;
        });
    }

    public function test_invalid_deletion_request_returns_404()
    {
        $owner = Admin::factory()->create();
        $owner->assignRole('owner');
        $response = $this->actingAs($owner, 'admin')
            ->post(route('admin.monitoring.deletion-records.hard-delete'), [
                'deletion_request_id' => 999999,
            ]);
        $response->assertStatus(404);
    }

    public function test_admin_without_permissions_cannot_access_any_deletion_routes()
    {
        // Revoke all permissions from the owner role
        $ownerRole = \Spatie\Permission\Models\Role::findByName('owner', 'admin');
        $ownerRole->revokePermissionTo($ownerRole->permissions);
        // Prepare a user and a deletion request for user
        $user = User::factory()->create();
        $userDeletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => User::class,
            'deletable_id' => $user->id,
        ]);

        // Prepare an admin and a deletion request for admin
        $adminToDelete = Admin::factory()->create(['deleted_at' => now()]);
        $adminDeletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => $adminToDelete->id,
        ]);

        // Soft delete a user for restore
        $softDeletedUser = User::factory()->create();
        $softDeletedUser->delete();
        $userRestoreRequest = DeletionRequest::factory()->create([
            'deletable_type' => User::class,
            'deletable_id' => $softDeletedUser->id,
        ]);

        // Soft delete an admin for restore
        $softDeletedAdmin = Admin::factory()->create(['deleted_at' => now()]);
        $adminRestoreRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => $softDeletedAdmin->id,
        ]);

        // Hard delete user
        $response = $this->post(route('admin.monitoring.deletion-records.hard-delete'), [
            'deletion_request_id' => $userDeletionRequest->id,
        ]);
        $response->assertStatus(403);

        // Hard delete admin
        $response = $this->post(route('admin.monitoring.deletion-records.hard-delete'), [
            'deletion_request_id' => $adminDeletionRequest->id,
            'admin_id' => $this->admin->id,
        ]);
        $response->assertStatus(403);

        // Restore user
        $response = $this->post(route('admin.monitoring.deletion-records.restore'), [
            'deletion_request_id' => $userRestoreRequest->id,
        ]);
        $response->assertStatus(403);

        // Restore admin
        $response = $this->post(route('admin.monitoring.deletion-records.restore'), [
            'deletion_request_id' => $adminRestoreRequest->id,
        ]);
        $response->assertStatus(403);
    }
} 