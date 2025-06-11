<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Database\Seeders\RoleSeeder;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\Bus;
use App\Models\Competition\Competition;
use App\Jobs\Competition\DeleteAuditorJob;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\Competition\CompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Competetion\SyncCompetitionParticipants;
use App\Http\Controllers\Competition\CompetitionController;

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
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_admin_can_successfully_create_competition()
    {
        Bus::fake();
        // Give the admin the 'add competition' permission
        $this->admin->givePermissionTo('add competition');
        
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
    }

    public function test_create_fails_with_unauthorized_admin()
    {
        $data = $this->createCompetitionData();
        $response = $this->post(route('admin.competitions.store'), $data);
        $response->assertStatus(403);
    }

    public function test_admin_can_successfully_update_competition()
    {
        Bus::fake();
        
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        
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

    public function it_can_display_competition_auditors()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $id_b64 = base64_encode($competition->id);

        $response = $this->get(route('admin.competitions.auditors', $id_b64));

        $response->assertOk()
            ->assertViewIs('pages.admin.competitions.competition_auditors')
            ->assertViewHas('competition');
    }

    public function test_admin_can_successfully_add_auditors_to_competition()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $auditors = Admin::factory()->count(3)->create();
        $auditorIds = $auditors->pluck('id')->toArray();

        $response = $this->post(route('admin.competitions.auditor.store', ['competition' => $competition]), [
            'auditor_ids' => implode(',', $auditorIds)
        ]);

        $response->assertRedirectBack();
        foreach ($auditorIds as $auditorId) {
            $this->assertDatabaseHas('admin_competition', [
                'competition_id' => $competition->id,
                'admin_id' => $auditorId
            ]);
        }
    }

    public function test_add_auditors_fails_with_unauthorized_admin()
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
            $this->assertDatabaseMissing('admin_competition', [
                'competition_id' => $competition->id,
                'admin_id' => $auditorId
            ]);
        }
    }

    public function test_admin_can_successfully_remove_auditor_from_competition()
    {
        Bus::fake();
        $competition = Competition::factory()->create(['admin_id' => $this->admin->id]);
        $auditor = Admin::factory()->count(2)->create();
        $competition->auditors()->attach($auditor->pluck('id')->toArray());

        $response = $this->post(route('admin.competitions.auditor.delete'), [
            'competition_id' => $competition->id,
            'auditor_id' => $auditor->first()->id
        ]);

        $response->assertRedirectBack();
        Bus::assertDispatched(DeleteAuditorJob::class, function ($job) use ($competition, $auditor) {
            return $job->getCompetition()->id === $competition->id
                && $job->getAuditorId() === $auditor->first()->id;
        });
    }

    public function test_remove_auditor_fails_with_unauthorized_admin()
    {
        Bus::fake();
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
        Bus::assertNotDispatched(DeleteAuditorJob::class);
    }

    public function test_admin_can_successfully_activate_competition()
    {
        $competition = Competition::factory()->create([
            'admin_id' => $this->admin->id,
            'start_date' => now()->subDay(),
            'levels_number' => 2
        ]);

        // Create required levels
        Level::factory()->create([
            'competition_id' => $competition->id,
            'start_date' => now()->addDay()
        ]);
        Level::factory()->create([
            'competition_id' => $competition->id,
            'start_date' => now()->addDays(2)
        ]);

        // Add required users
        $users = User::factory()->count(3)->create();
        $competition->users()->attach($users->pluck('id'));

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
            'status' => 1
        ]);
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
            'status' => 0
        ]);
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