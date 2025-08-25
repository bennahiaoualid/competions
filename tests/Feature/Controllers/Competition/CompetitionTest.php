<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Database\Seeders\RoleSeeder;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\Bus;
use App\Enums\AdminApprovalTypeEnum;
use App\Models\Competition\Competition;
use Illuminate\Foundation\Testing\WithFaker;
use App\Jobs\Notifications\BatchBroadcastJob;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use App\Jobs\Notifications\BatchNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Competetion\SyncCompetitionParticipants;

class CompetitionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @var Admin */
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data that will be used across multiple tests
        $this->admin = Admin::factory()->create();
        
        // seed role
        $this->seed(RoleSeeder::class);
        
        // Authenticate as admin for tests that require authentication
        $this->actingAs($this->admin, 'admin');

        Bus::fake();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_admin_can_successfully_create_competition()
    {
        
        // Give the admin the 'add competition' permission
        $this->admin->givePermissionTo('add competition');
        
        // Create eligible users (age 18-25) before creating competition
        $eligibleUsers = User::factory()->count(3)->create([
            'birthdate' => $this->faker->dateTimeBetween('2000-01-01', '2007-01-01')->format('Y-m-d'),
            'email_verified_at' => now(), // Ensure they are verified
        ]);
        
        $competitionData = $this->createCompetitionData();

        $response = $this->post(route('admin.competitions.store'), $competitionData);

        $response->assertRedirectBack();
        $this->assertDatabaseHas('competitions', [
            'title' => $competitionData['title'],
            'description' => $competitionData['description'],
            'admin_id' => $this->admin->id,
            'age_start' => $competitionData['age_start'],
            'age_end' => $competitionData['age_end'],
            'levels_number' => $competitionData['levels_number'],
        ]);
        Bus::assertDispatched(SyncCompetitionParticipants::class);
        Bus::assertDispatched(BatchNotificationJob::class);
    }

    public function test_create_fails_with_invalid_data()
    {
        $this->admin->givePermissionTo('add competition');

        $data = $this->createCompetitionData();
        $data['age_start'] = 30;
        $data['age_end'] = 20; 
        $data['levels_number'] = 0;
        $data['start_date'] = now()->subDays(1)->format('Y-m-d H:i');
        unset($data['title']);

        $response = $this->post(route('admin.competitions.store'), $data);

        $response->assertSessionHasErrors(
            [
                //'age_end',
                'levels_number',
                'start_date',
                'title',
            ],
            errorBag:'createCompetition'
        );

        Bus::assertNothingDispatched();
    }

    public function test_create_fails_with_unauthorized_admin()
    {
        $data = $this->createCompetitionData();
        $response = $this->post(route('admin.competitions.store'), $data);
        $response->assertStatus(403);
    }

    public function test_admin_can_successfully_update_competition()
    {
        
        
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        // Create eligible users (age 18-25) before creating competition
        $eligibleUsers = User::factory()->count(3)->create([
            'birthdate' => $this->faker->dateTimeBetween('2000-01-01', '2007-01-01')->format('Y-m-d'),
            'email_verified_at' => now(), // Ensure they are verified
        ]);
        $competition->users()->attach($eligibleUsers->pluck('id')->toArray());
        $updateData = [
            'start_date' => now()->addDays(7)->format('Y-m-d H:i'),
            'age_start' => 18,
            'age_end' => 25,
        ];

        $response = $this->patch(route('admin.competitions.update', ['competition' => $competition]), $updateData);

        $response->assertRedirectBack();
        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
            'age_start' => $updateData['age_start'],
            'age_end' => $updateData['age_end'],
        ]);
        Bus::assertDispatched(SyncCompetitionParticipants::class);
        Bus::assertDispatched(BatchNotificationJob::class);
    }

    public function test_update_fails_with_invalid_data()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        
        $data = [
            'age_start' => 30,
            'age_end' => 20, // age_end less than age_start
            'start_date' => now()->subDays(1)->format('Y-m-d H:i'), // past date
        ];

        $response = $this->patch(route('admin.competitions.update', ['competition' => $competition]), $data);

        $response->assertSessionHasErrors(
            [
                'age_end',
                'start_date',
            ],
            errorBag: 'updateCompetition'
        );

        Bus::assertNothingDispatched();
    }

    public function test_update_fails_with_unauthorized_admin()
    {
        $new_admin = Admin::factory()->create();

        $competition = Competition::factory()->create(['admin_id' => $new_admin->id]);
        
        $data = [
            'start_date' => now()->addDays(7)->format('Y-m-d H:i'),
            'age_start' => 18,
            'age_end' => 25,
        ];

        $response = $this->patch(route('admin.competitions.update', ['competition' => $competition]), $data);
        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );  

        Bus::assertNothingDispatched();
    }

    public function test_admin_can_successfully_delete_competition()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);

        $response = $this->post(route('admin.competitions.delete'), [
            'id' => $competition->id
        ]);

        $response->assertRedirectBack();
        $this->assertDatabaseMissing('competitions', [
            'id' => $competition->id
        ]);
    }

    public function test_delete_fails_with_unauthorized_admin()
    {
        $new_admin = Admin::factory()->create();
        $competition = Competition::factory()->create(['admin_id' => $new_admin->id]);

        $response = $this->post(route('admin.competitions.delete'), [
            'id' => $competition->id
        ]);

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_delete'),
            session()->get('messages')[0]['message']
        );
        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
        ]);
    }

    public function test_admin_can_successfully_add_users_to_competition()
    {
        
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $response = $this->post(route('admin.competitions.users.store', $competition), [
            'user_ids' => implode(',', $userIds)
        ]);

        $response->assertRedirectBack();
        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('competition_user', [
                'competition_id' => $competition->id,
                'user_id' => $userId
            ]);
        }
        Bus::assertDispatched(BatchNotificationJob::class);
    }

    public function test_add_users_fails_with_unauthorized_admin()
    {
        $new_admin = Admin::factory()->create();
        $competition = Competition::factory()->create(['admin_id' => $new_admin->id]);
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $response = $this->post(route('admin.competitions.users.store', $competition), [
            'user_ids' => implode(',', $userIds)
        ]);

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );
        foreach ($userIds as $userId) {
            $this->assertDatabaseMissing('competition_user', [
                'competition_id' => $competition->id,
                'user_id' => $userId
            ]);
        }

        Bus::assertNothingDispatched();
    }

    public function test_admin_can_successfully_remove_user_from_competition()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $user = User::factory()->create();
        $competition->users()->attach($user->id);

        $response = $this->post(route('admin.competitions.users.delete'), [
            'competition_id' => $competition->id,
            'user_id' => $user->id
        ]);

        $response->assertRedirectBack();
        $this->assertDatabaseMissing('competition_user', [
            'competition_id' => $competition->id,
            'user_id' => $user->id
        ]);

        Bus::assertNothingDispatched();
    }

    public function test_remove_user_fails_with_unauthorized_admin()
    {
        $new_admin = Admin::factory()->create();
        $competition = Competition::factory()->create(['admin_id' => $new_admin->id]);
        $user = User::factory()->create();
        $competition->users()->attach($user->id);

        $response = $this->post(route('admin.competitions.users.delete'), [
            'competition_id' => $competition->id,
            'user_id' => $user->id
        ]);

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );
        $this->assertDatabaseHas('competition_user', [
            'competition_id' => $competition->id,
            'user_id' => $user->id
        ]);
    }

    public function test_remove_user_fails_with_invalid_data()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $user = User::factory()->create();

        $response = $this->post(route('admin.competitions.users.delete'), [
            'competition_id' => 'invalid',
            'user_id' => 'invalid'
        ]);

        $response->assertRedirectBack();
        $response->assertSessionHasErrors(['competition_id', 'user_id']);
    }

    public function test_admin_can_successfully_request_auditor_assignment()
    {
        
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $auditors = Admin::factory()->count(3)->create();
        // Create availability records for these auditors
        foreach ($auditors as $auditor) {
            $auditor->availability()->first()->update(['auditor' => true]);
        }
        $auditorIds = $auditors->pluck('id')->toArray();
        
        $response = $this->post(route('admin.competitions.auditor.store', ['competition' => $competition]), [
            'auditor_ids' => implode(',', $auditorIds)
        ]);

        $response->assertRedirectBack();
        $competition->fresh();

        foreach ($auditorIds as $auditorId) {
            $this->assertDatabaseHas('admin_approvals', [
                'admin_id' => $auditorId,
                'entity_type' => Competition::class,
                'entity_id' => $competition->id,
                'type' => AdminApprovalTypeEnum::AUDITOR->value
            ]);
        }
        Bus::assertDispatched(BatchNotificationJob::class);
    }

    public function test_request_auditor_assignment_fails_with_unauthorized_admin()
    {
        
        $new_admin = Admin::factory()->create();
        $competition = Competition::factory()->create(['admin_id' => $new_admin->id]);
        $auditors = Admin::factory()->count(3)->create();
        $auditorIds = $auditors->pluck('id')->toArray();

        $response = $this->post(route('admin.competitions.auditor.store', ['competition' => $competition]), [
            'auditor_ids' => implode(',', $auditorIds)
        ]);

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );
        foreach ($auditorIds as $auditorId) {
            $this->assertDatabaseMissing('admin_approvals', [
                'admin_id' => $auditorId,
                'entity_type' => Competition::class,
                'entity_id' => $competition->id,
                'type' => AdminApprovalTypeEnum::AUDITOR->value
            ]);
        }
        Bus::assertNothingDispatched();
    }

    public function test_admin_can_successfully_remove_auditor_from_competition()
    {
        
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $auditor = Admin::factory()->count(2)->create();
        $competition->auditors()->attach($auditor->pluck('id')->toArray());

        $response = $this->post(route('admin.competitions.auditor.delete'), [
            'competition_id' => $competition->id,
            'auditor_id' => $auditor->first()->id
        ]);

        $response->assertRedirectBack();
        Bus::assertDispatched(SafeDeleteAuditorJob::class, function ($job) use ($competition, $auditor) {
            return $job->getCompetition()->id === $competition->id
                && $job->getAuditor()->id === $auditor->first()->id;
        });
    }

    public function test_remove_auditor_fails_with_unauthorized_admin()
    {
        
        $new_admin = Admin::factory()->create();
        $competition = Competition::factory()->create(['admin_id' => $new_admin->id]);
        $auditor = Admin::factory()->create();
        $competition->auditors()->attach($auditor->id);

        $response = $this->post(route('admin.competitions.auditor.delete'), [
            'competition_id' => $competition->id,
            'auditor_id' => $auditor->id
        ]);

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );
        $this->assertDatabaseHas('admin_competition', [
            'competition_id' => $competition->id,
            'admin_id' => $auditor->id
        ]);
        Bus::assertNothingDispatched();
    }

    public function test_admin_can_successfully_activate_competition()
    {
        
        $competition = Competition::factory()->create([
            'admin_id' => $this->admin->id,
            'start_date' => now()->subDay(),
            'levels_number' => 2
        ]);

        // Create eligible users (age 18-25) before creating competition
        $eligibleUsers = User::factory()->count(3)->create([
            'birthdate' => $this->faker->dateTimeBetween('2000-01-01', '2007-01-01')->format('Y-m-d'),
            'email_verified_at' => now(), // Ensure they are verified
        ]);
        $competition->users()->attach($eligibleUsers->pluck('id')->toArray());

        // Create required levels
        Level::factory()->create([
            'competition_id' => $competition->id,
            'start_date' => now()->addDay()
        ]);
        Level::factory()->create([
            'competition_id' => $competition->id,
            'start_date' => now()->addDays(2)
        ]);

        // Add required auditor
        $auditor = Admin::factory()->create();
        $competition->auditors()->attach($auditor->id);

        $response = $this->post(route('admin.competitions.activate', ['competition' => $competition]));

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.success.activated'),
            session()->get('messages')[0]['message']
        );
        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
            'status' => Competition::STATUS_ACTIVE
        ]);
        Bus::assertDispatched(BatchNotificationJob::class);
        Bus::assertDispatched(BatchBroadcastJob::class);
    }

    public function test_activate_fails_when_start_date_is_in_future()
    {
        $competition = Competition::factory()->create([
            'admin_id' => $this->admin->id,
            'start_date' => now()->addDay(),
            'levels_number' => 1
        ]);

        $response = $this->post(route('admin.competitions.activate', ['competition' => $competition]));

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_activate_early'),
            session()->get('messages')[0]['message']
        );
        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
            'status' => Competition::STATUS_PENDING
        ]);

        Bus::assertNothingDispatched();
    }
    /** private methods */
    private function createCompetitionData()
    {
        return [
            'title' => 'Laravel Competition 2024',
            'description' => 'Laravel Competition 2024',
            'start_date' => now()->addDays(7)->format('Y-m-d H:i'),
            'age_start' => 18,
            'age_end' => 25,
            'levels_number' => 1,
        ];
    }
} 