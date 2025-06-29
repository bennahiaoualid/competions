<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Admin $admin;
    protected Admin $owner;
    protected Admin $superAdmin;
    protected Admin $manager;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshApplicationWithLocale('en');
        
        // Seed roles and permissions
        $this->seed(RoleSeeder::class);
        
        // Create test admins with different roles
        $this->owner = Admin::factory()->create();
        $this->owner->assignRole('owner');
        
        $this->superAdmin = Admin::factory()->create();
        $this->superAdmin->assignRole('super_admin');
        
        $this->manager = Admin::factory()->create();
        $this->manager->assignRole('manager');
        
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('super_admin');
        
        // Create a regular user for count testing
        $this->user = User::factory()->create();
        
        // Fake queues for job testing
        Bus::fake();
        Queue::fake();
    }

    // ========================================
    // AUTHENTICATION & AUTHORIZATION TESTS
    // ========================================

    /**
     * Test unauthenticated users are redirected to login
     */
    public function test_unauthenticated_users_are_redirected_to_login()
    {
        $response = $this->get(route('admin.index'));
        $response->assertRedirect(route('login'));
        
        $response = $this->get(route('admin.list'));
        $response->assertRedirect(route('login'));
        
        $response = $this->post(route('admin.store'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test authenticated users can access dashboard and list
     */
    public function test_authenticated_admins_can_access_dashboard_and_list()
    {
        $this->actingAs($this->admin, 'admin');
        
        $response = $this->get(route('admin.index'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.dashboard');
        
        $response = $this->get(route('admin.list'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.admins.list');
    }

    /**
     * Test only owner and super_admin can create admins
     */
    public function test_only_owner_and_super_admin_can_create_admins()
    {
        // Owner can create
        $this->actingAs($this->owner, 'admin');
        $response = $this->post(route('admin.store'), $this->createAdminData());
        $response->assertRedirectBack();
        
        // Super admin can create
        $this->actingAs($this->superAdmin, 'admin');
        $response = $this->post(route('admin.store'), $this->createAdminData());
        $response->assertRedirectBack();
        
        // Manager cannot create
        $this->actingAs($this->manager, 'admin');
        $response = $this->post(route('admin.store'), $this->createAdminData());
        $response->assertStatus(403);
    }

    /**
     * Test admin cannot edit themselves
     */
    public function test_admin_cannot_edit_themselves()
    {
        $this->actingAs($this->admin, 'admin');
        
        $response = $this->get(route('admin.edit', ['id' => $this->admin->id]));
        $response->assertRedirect(route('admin.profile.edit'));
    }

    /**
     * Test admin cannot edit admin with higher or equal privilege
     */
    public function test_admin_cannot_edit_admin_with_higher_or_equal_privilege()
    {
        $this->actingAs($this->superAdmin, 'admin');
        
        // Super admin cannot edit owner
        $response = $this->get(route('admin.edit', ['id' => $this->owner->id]));
        $response->assertRedirect(route('admin.profile.edit'));
        
        // Super admin cannot edit another super admin
        $response = $this->get(route('admin.edit', ['id' => $this->admin->id]));
        $response->assertRedirect(route('admin.profile.edit'));
    }

    /**
     * Test admin can edit admin with lower privilege
     */
    public function test_admin_can_edit_admin_with_lower_privilege()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->get(route('admin.edit', ['id' => $this->superAdmin->id]));
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.admins.edit-admin');
    }

    /**
     * Test only owner can delete admins
     */
    public function test_only_owner_can_delete_admins()
    {
        $adminToDelete1 = Admin::factory()->create();
        $adminToDelete2 = Admin::factory()->create(['admin_id' => $this->admin->id]);
        
        // Owner can delete
        $this->actingAs($this->owner, 'admin');
        $response = $this->post(route('admin.delete'), ['id' => $adminToDelete1->id]);
        $response->assertRedirect();

        session()->flush();

        // Super admin cannot delete
        $this->actingAs($this->superAdmin, 'admin');
        $response = $this->post(route('admin.delete'), ['id' => $adminToDelete2->id]);
        $response->assertRedirect();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.admin_delete'),
            session()->get('messages')[0]['message']
        );
    }

    // ========================================
    // DASHBOARD TESTS
    // ========================================

    /**
     * Test dashboard returns correct view with counts
     */
    public function test_dashboard_returns_correct_view_with_counts()
    {
        $this->actingAs($this->admin, 'admin');
        
        $response = $this->get(route('admin.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.dashboard');
        $response->assertViewHas('count');
        $response->assertViewHas('count.admin');
        $response->assertViewHas('count.user');
    }

    /**
     * Test dashboard shows correct admin and user counts
     */
    public function test_dashboard_shows_correct_counts()
    {
        $this->actingAs($this->admin, 'admin');
        
        // Create additional admins and users
        Admin::factory()->count(3)->create();
        User::factory()->count(5)->create();
        
        $response = $this->get(route('admin.index'));
        
        $response->assertStatus(200);
        $response->assertViewHas('count', function ($count) {
            return $count['admin'] >= 4 && $count['user'] >= 6;
        });
    }

    // ========================================
    // ADMIN LIST TESTS
    // ========================================

    /**
     * Test admin list returns correct view with roles
     */
    public function test_admin_list_returns_correct_view_with_roles()
    {
        $this->actingAs($this->admin, 'admin');
        
        $response = $this->get(route('admin.list'));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.admins.list');
        $response->assertViewHas('roles');
    }

    /**
     * Test admin list shows correct roles based on user role
     */
    public function test_super_admin_list_shows_limited_roles()
    {
        // Super admin should see limited roles
        $this->actingAs($this->superAdmin, 'admin');
        $response = $this->get(route('admin.list'));
        $response->assertViewHas('roles', function ($roles) {
            return !$roles->contains('name', 'super_admin') && !$roles->contains('name', 'owner');
        });
    }
    public function test_owner_list_shows_all_roles()
    {
        // Owner should see all roles
        $this->actingAs($this->owner, 'admin');
        $response = $this->get(route('admin.list'));
        $response->assertViewHas('roles', function ($roles) {
            return $roles->contains('name', 'super_admin') && $roles->contains('name', 'owner');
        });
    }

    // ========================================
    // CREATE ADMIN TESTS
    // ========================================

    /**
     * Test successful admin creation
     */
    public function test_successful_admin_creation()
    {
        $this->actingAs($this->owner, 'admin');
        
        $adminData = $this->createAdminData();
        
        $response = $this->post(route('admin.store'), $adminData);
        
        $response->assertRedirectBack();

        $this->assertDatabaseHas('admins', [
            'name' => $adminData['name'],
            'email' => $adminData['email'],
            'birthdate' => $adminData['birthdate'],
            'gender' => $adminData['gender'],
            'admin_id' => $this->owner->id,
        ]);
        
        // Check role assignment
        $newAdmin = Admin::where('email', $adminData['email'])->first();
        $this->assertTrue($newAdmin->hasRole($adminData['role']));
    }

    /**
     * Test admin creation with validation errors
     */
    public function test_admin_creation_with_validation_errors()
    {
        $this->actingAs($this->owner, 'admin');
        
        $invalidData = [
            'name' => 'ab', // too short
            'email' => 'invalid-email', // invalid email
            'birthdate' => 'invalid-date', // invalid date
            'gender' => 'invalid-gender', // invalid gender
            'role' => 999, // invalid role
            'password' => '123', // too short
        ];
        
        $response = $this->post(route('admin.store'), $invalidData);
        
        $response->assertSessionHasErrors([
            'name',
            'email',
            'birthdate',
            'gender',
            'role',
            'password',
        ], errorBag:'createAdmin');
    }

    /**
     * Test admin creation with duplicate email
     */
    public function test_admin_creation_with_duplicate_email()
    {
        $this->actingAs($this->owner, 'admin');
        
        $existingAdmin = Admin::factory()->create();
        $adminData = $this->createAdminData(['email' => $existingAdmin->email]);
        
        $response = $this->post(route('admin.store'), $adminData);
        
        $response->assertSessionHasErrors(['email'], errorBag:'createAdmin');
    }

    /**
     * Test admin creation with invalid role assignment
     */
    public function test_admin_creation_with_invalid_role_assignment()
    {
        $this->actingAs($this->superAdmin, 'admin');
        
        // Super admin cannot assign owner role
        $adminData = $this->createAdminData(['role' => Role::where('name', 'owner')->first()->id]);
        
        $response = $this->post(route('admin.store'), $adminData);
        
        $response->assertSessionHasErrors(['role'], errorBag:'createAdmin');
    }

    // ========================================
    // EDIT ADMIN TESTS
    // ========================================

    /**
     * Test successful admin edit form display
     */
    public function test_successful_admin_edit_form_display()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->get(route('admin.edit', ['id' => $this->superAdmin->id]));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.admins.edit-admin');
        $response->assertViewHas('admin', $this->superAdmin);
        $response->assertViewHas('roles');
    }

    /**
     * Test edit form with non-existent admin
     */
    public function test_edit_form_with_non_existent_admin()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->get(route('admin.edit', ['id' => 99999]));
        
        $response->assertStatus(404);
    }

    // ========================================
    // UPDATE ADMIN TESTS
    // ========================================

    /**
     * Test successful admin update
     */
    public function test_successful_admin_update()
    {
        $this->actingAs($this->owner, 'admin');
        
        $updateData = [
            'id' => $this->superAdmin->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => Role::where('name', 'manager')->first()->id,
        ];
        
        $response = $this->patch(route('admin.update'), $updateData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('admins', [
            'id' => $this->superAdmin->id,
            'name' => $updateData['name'],
            'email' => $updateData['email'],
        ]);
        
        // Check role update
        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->hasRole('manager'));
    }

    /**
     * Test admin update with validation errors
     */
    public function test_admin_update_with_validation_errors()
    {
        $this->actingAs($this->owner, 'admin');
        
        $invalidData = [
            'id' => $this->superAdmin->id,
            'name' => 'ab', // too short
            'email' => 'invalid-email', // invalid email
            'role' => 999, // invalid role
        ];
        
        $response = $this->patch(route('admin.update'), $invalidData);
        
        $response->assertSessionHasErrors([
            'name',
            'email',
            'role',
        ], errorBag:'updateAdmin');
    }

    /**
     * Test admin update with duplicate email
     */
    public function test_admin_update_with_duplicate_email()
    {
        $this->actingAs($this->owner, 'admin');
        
        $otherAdmin = Admin::factory()->create();
        $updateData = [
            'id' => $this->superAdmin->id,
            'name' => 'Updated Name',
            'email' => $otherAdmin->email,
            'role' => Role::where('name', 'manager')->first()->id,
        ];
        
        $response = $this->patch(route('admin.update'), $updateData);
        
        $response->assertSessionHasErrors(['email'], errorBag:'updateAdmin');
    }

    /**
     * Test admin update with same email (should not error)
     */
    public function test_admin_update_with_same_email()
    {
        $this->actingAs($this->owner, 'admin');
        
        $updateData = [
            'id' => $this->superAdmin->id,
            'name' => 'Updated Name',
            'email' => $this->superAdmin->email,
            'role' => Role::where('name', 'manager')->first()->id,
        ];
        
        $response = $this->patch(route('admin.update'), $updateData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('admins', [
            'id' => $this->superAdmin->id,
            'name' => $updateData['name'],
            'email' => $updateData['email'],
        ]);
    }

    // ========================================
    // DELETE ADMIN TESTS
    // ========================================

    /**
     * Test successful admin deletion
     */
    public function test_successful_admin_deletion()
    {
        $this->actingAs($this->owner, 'admin');
        
        $adminToDelete = Admin::factory()->create(['admin_id' => $this->manager->id]);
        
        $response = $this->post(route('admin.delete'), ['id' => $adminToDelete->id]);
        
        $response->assertRedirect();
        $adminToDelete->refresh();
        $this->assertTrue($adminToDelete->trashed());
        
        // Verify job was dispatched
        Bus::assertDispatched(SafeDeleteAuditorJob::class);
    }

    /**
     * Test admin deletion with non-existent admin
     */
    public function test_admin_deletion_with_non_existent_admin()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->post(route('admin.delete'), ['id' => 99999]);
        
        $response->assertStatus(404);
    }

    /**
     * Test admin cannot delete themselves
     */
    public function test_admin_cannot_delete_themselves()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->post(route('admin.delete'), ['id' => $this->owner->id]);
        
        $response->assertRedirect();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.admin_delete'),
            session()->get('messages')[0]['message']
        );
        $this->owner->refresh();
        $this->assertTrue($this->owner->exists());
    }

    /**
     * Test admin deletion with job queue verification
     */
    public function test_admin_deletion_dispatches_job()
    {
        $this->actingAs($this->owner, 'admin');
        
        $adminToDelete = Admin::factory()->create();
        
        $response = $this->post(route('admin.delete'), ['id' => $adminToDelete->id]);
        
        $response->assertRedirect();
        
        // Verify the specific job was dispatched with correct parameters
        Bus::assertDispatched(SafeDeleteAuditorJob::class, function ($job) use ($adminToDelete) {
            return $job->getAuditor()->id === $adminToDelete->id;
        });
    }

    public function test_admin_restoration_after_job_failure()
    {
        $adminToDelete = Admin::factory()->create();
        
        // Soft delete admin first
        $adminToDelete->delete();
        $this->assertSoftDeleted('admins', ['id' => $adminToDelete->id]);
        
        // Create job instance
        $job = new SafeDeleteAuditorJob($adminToDelete, null, Auth::id());
        
        // Create a mock JobTracking record
        $tracking = new \App\Models\Monitoring\JobTracking([
            'job_id' => $job->getTrackingId(),
            'job_class' => SafeDeleteAuditorJob::class,
            'job_type' => 'admin',
            'status' => 'failed',
            'attempts' => 3,
            'max_attempts' => 3,
        ]);
        
        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($job);
        $onFinalFailureMethod = $reflection->getMethod('onFinalFailure');
        $onFinalFailureMethod->setAccessible(true);
        
        // Call the protected method
        $exception = new \Exception('Job failed');
        $onFinalFailureMethod->invoke($job, $exception, $tracking);
        
        // Verify admin was restored
        $adminToDelete->refresh();
        $this->assertNull($adminToDelete->deleted_at, 'Admin should be restored after onFinalFailure');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Create admin data for testing
     */
    private function createAdminData(array $overrides = []): array
    {
        $role = Role::where('name', 'manager')->first();
        
        return array_merge([
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'birthdate' => $this->faker->date('Y-m-d', '2002-01-01'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'password' => 'password123',
            'role' => $role->id,
        ], $overrides);
    }
} 