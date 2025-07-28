<?php

namespace Tests\Feature\Controllers\Competition;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Database\Seeders\RoleSeeder;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\Bus;
use App\Models\Competition\Question;
use App\Models\Competition\Competition;
use App\Jobs\Competition\FinishLevelJob;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Notifications\BatchBroadcastNotificationJob;
use App\Jobs\Notifications\BatchCompetitionNotificationJob;

class LevelControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected Admin $admin;
    protected Competition $competition;
    protected Level $level;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data that will be used across multiple tests
        $this->admin = Admin::factory()->create();
        $this->admin->availability()->update(['level_manager' => true]);
        
        // seed role
        $this->seed(RoleSeeder::class);
        
        // Create a competition
        $this->competition = Competition::factory()->create([
            'admin_id' => $this->admin->id,
            'age_start' => 18,
            'age_end' => 25,
            'status' => 'pending', // Not activated
            'start_date' => now()->addDays(7),
            'levels_number' => 3
        ]);

        // Create eligible users (age 18-25) before creating competition
        $eligibleUsers = User::factory()->count(3)->create([
            'birthdate' => $this->faker->dateTimeBetween('2000-01-01', '2007-01-01')->format('Y-m-d'),
            'email_verified_at' => now(), // Ensure they are verified
        ]);
        $this->competition->users()->attach($eligibleUsers->pluck('id')->toArray());

        // Create a level
        $this->level = Level::factory()->create([
            'competition_id' => $this->competition->id,
            'status' => 'pending', // Not activated
            'start_date' => now()->addDays(8),
            'duration' => 60, // 60 minutes
            'questions_number' => 5
        ]);
        
        // Authenticate as admin for tests that require authentication
        $this->actingAs($this->admin, 'admin');

        Bus::fake();
    }

    
    public function test_can_store_a_new_level()
    {
        $levelData = [
            'name' => 'Test Level',
            'description' => 'Test Description',
            'start_date' => now()->addDays(9)->format('Y-m-d H:i'),
            'duration' => 45,
            'questions_number' => 3,
            'admin_id' => $this->admin->id
        ];

        $response = $this->post(route('admin.competitions.level.store', $this->competition), $levelData);

        $response->assertRedirectBack();
        $this->assertDatabaseHas('levels', [
            'name' => $levelData['name'],
            'description' => $levelData['description'],
            'duration' => $levelData['duration'],
            'questions_number' => $levelData['questions_number'],
            'competition_id' =>$this->competition->id
        ]);
        Bus::assertDispatched(BatchCompetitionNotificationJob::class);
    }

    
    public function test_validates_required_fields_when_storing_level()
    {
        $response = $this->post(route('admin.competitions.level.store', $this->competition), []);

        $response->assertSessionHasErrors([
            'name',
            'start_date',
            'duration',
            'questions_number',
            'admin_id'
        ], errorBag:'createLevel');
    }

    
    public function test_can_not_store_level_start_date_must_be_after_competition_start()
    {
        $levelData = [
            'name' => 'Test Level',
            'description' => 'Test Description',
            'start_date' => now()->addDays(5)->format('Y-m-d H:i'), // Before competition start
            'duration' => 45,
            'questions_number' => 3,
            'admin_id' => $this->admin->id
        ];

        $response = $this->post(route('admin.competitions.level.store', $this->competition), $levelData);

        $this->assertStringContainsString(
            trans('validation.custom.start_date_gt_competition'),
            session()->get('messages')[0]['message']
        );
    }

    
    public function test_can_not_store_level_maximum_number_of_levels()
    {
        // Create maximum number of levels
        for ($i = 1; $i < $this->competition->levels_number; $i++) {
            Level::factory()->create([
                'competition_id' => $this->competition->id,
                'start_date' => now()->addDays(8 + $i),
                'admin_id' => $this->admin->id
            ]);
        }

        $levelData = [
            'name' => 'Extra Level',
            'description' => 'Test Description',
            'start_date' => now()->addDays(20)->format('Y-m-d H:i'),
            'duration' => 45,
            'questions_number' => 3,
            'admin_id' => $this->admin->id
        ];

        $response = $this->post(route('admin.competitions.level.store', $this->competition), $levelData);

        $response->assertRedirect();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_max_levels'),
            session()->get('messages')[0]['message']
        );
    }

    public function test_can_not_store_level_unauthorized_admin()
    {
        $otherAdmin = Admin::factory()->create();
        $competition = Competition::factory()->create([
            'admin_id' => $otherAdmin->id
        ]);
        $levelData = [
            'name' => 'Test Level',
            'description' => 'Test Description',
            'start_date' => now()->addDays(8)->format('Y-m-d H:i'),
            'duration' => 45,
            'questions_number' => 3,
            'admin_id' => $this->admin->id
        ];

        $response = $this->post(route('admin.competitions.level.store', $competition), $levelData);           

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );
        $this->assertDatabaseMissing('levels', [
            'name' => $levelData['name'],
            'competition_id' => $competition->id
        ]);
    }

    public function test_can_not_store_level_admin_not_available_as_manager()
    {
        $otherAdmin = Admin::factory()->create();
        $levelData = [
            'name' => 'Test Level',
            'description' => 'Test Description',
            'start_date' => now()->addDays(8)->format('Y-m-d H:i'),
            'duration' => 45,
            'questions_number' => 3,
            'admin_id' => $otherAdmin->id
        ];

        $response = $this->post(route('admin.competitions.level.store', $this->competition), $levelData);           

        $response->assertRedirectBack();
        $this->assertDatabaseMissing('levels', [
            'name' => $levelData['name'],
            'competition_id' => $this->competition->id
        ]);
    }
    
    public function test_can_update_level_successfully()
    {
        $updateData = [
            'name' => 'Updated Level',
            'description' => 'Updated Description',
            'start_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'duration' => 90,
            'admin_id' => $this->admin->id
        ];

        $response = $this->patch(route('admin.competitions.level.update', $this->level), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('levels', [
            'id' => $this->level->id,
            'name' => $updateData['name'],
            'description' => $updateData['description'],
            'duration' => $updateData['duration']
        ]);
        Bus::assertDispatched(BatchCompetitionNotificationJob::class);
    }

    public function test_can_not_update_level_unauthorized_admin()
    {
        $otherAdmin = Admin::factory()->create();
        $this->actingAs($otherAdmin, 'admin');

        $updateData = [
            'name' => 'Updated Level',
            'description' => 'Updated Description',
            'start_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'duration' => 90,
            'admin_id' => $this->admin->id
        ];

        $response = $this->patch(route('admin.competitions.level.update', $this->level), $updateData);

        $response->assertForbidden();
    }
    
    public function test_can_not_update_level_invalid_data()
    {
        $updateData = [
            'name' => '',
            'admin_id' => 10000000
        ];

        $response = $this->patch(route('admin.competitions.level.update', $this->level), $updateData);

        $response->assertSessionHasErrors([
            'name',
            'start_date',
            'duration',
            'admin_id'
        ], errorBag:'updateLevel');
    }

    public function test_can_not_update_level_has_time_conflict()
    {
        Level::factory()->create([
            'competition_id' => $this->competition->id,
            'start_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'admin_id' => $this->admin->id
        ]);
        $updateData = [
            'name' => 'Updated Level',
            'description' => 'Updated Description',
            'start_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'duration' => 90,
            'admin_id' => $this->admin->id
        ];

        $response = $this->patch(route('admin.competitions.level.update', $this->level), $updateData);

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.level_activate_time_conflict'),
            session()->get('messages')[0]['message']
        );
    }

    public function test_can_not_update_level_admin_not_available_as_manager()
    {
        $updateData = [
            'name' => 'Updated Level',
            'description' => 'Updated Description',
            'start_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'duration' => 90,
            'admin_id' =>  (Admin::factory()->create())->id
        ];

        $response = $this->patch(route('admin.competitions.level.update', $this->level), $updateData);

        $response->assertRedirectBack();
        $this->assertDatabaseMissing('levels', [
            'id' => $this->level->id,
            'name' => $updateData['name'],
            'competition_id' => $this->competition->id
        ]);

        $this->assertDatabaseHas('levels', [
            'id' => $this->level->id,
            'name' => $this->level->name,
            'competition_id' => $this->competition->id
        ]);
    }

    public function test_can_delete_level_successfully()
    {
        $response = $this->delete(route('admin.competitions.level.delete', $this->level));

        $response->assertRedirect();
        $this->assertDatabaseMissing('levels', ['id' => $this->level->id]);
    }

    
    public function test_can_not_delete_activated_competition()
    {
        $this->competition->update(['status' => 'active']);

        $response = $this->delete(route('admin.competitions.level.delete', $this->level));

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.active_competition_update'),
            session()->get('messages')[0]['message']
        );
    }

    public function test_can_not_delete_unauthorized_admin()
    {
        $otherAdmin = Admin::factory()->create();
        $this->actingAs($otherAdmin, 'admin');

        $response = $this->delete(route('admin.competitions.level.delete', $this->level));

        $response->assertRedirectBack();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );
    }

    
    public function test_can_activate_level_successfully()
    {
        // Setup required conditions
        $this->competition->update(['status' => 'active']); // Activate competition
        $this->level->update(['start_date' => now()->subHour()]); // Set start date in past
        
        // Create required number of questions
        Question::factory()->count($this->level->questions_number)->create([
            'level_id' => $this->level->id
        ]);

        $response = $this->post(route('admin.competitions.level.activate', $this->level));

        $response->assertRedirect();
        $this->assertDatabaseHas('levels', [
            'id' => $this->level->id,
            'status' => 'active'
        ]);
        Bus::assertDispatched(BatchCompetitionNotificationJob::class);
        Bus::assertDispatched(BatchBroadcastNotificationJob::class);
    }

    
    public function test_cannot_activate_level_without_required_questions()
    {
        $this->competition->update(['status' => 'active']);
        $this->level->update(['start_date' => now()->subHour()]);

        $response = $this->post(route('admin.competitions.level.activate', $this->level));

        $response->assertRedirect();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.level_activate_match_questions'),
            session()->get('messages')[0]['message']
        );
    }

    public function test_can_not_activate_level_unauthorized_admin()
    {
        $otherAdmin = Admin::factory()->create();
        $this->actingAs($otherAdmin, 'admin');

        $response = $this->post(route('admin.competitions.level.activate', $this->level));

        $response->assertRedirect();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            session()->get('messages')[0]['message']
        );
    }

    
    public function test_can_finish_level_successfully()
    {
        // Setup required conditions
        $this->level->update([
            'status' => 'active',
            'start_date' => now()->subHours(2),
            'duration' => 60
        ]);

        $response = $this->post(route('admin.competitions.level.finish', $this->level));

        $response->assertRedirectBack();
        Bus::assertDispatched(FinishLevelJob::class, function ($job) {
            return $job->getLevel()->id === $this->level->id;
        });
        Bus::assertDispatched(BatchCompetitionNotificationJob::class);
        Bus::assertDispatched(BatchBroadcastNotificationJob::class);
    }

    
    public function test_cannot_finish_active_level()
    {
        Bus::fake();
        $this->level->update([
            'status' => 'active',
            'start_date' => now()->subMinutes(30),
            'duration' => 60
        ]);

        $response = $this->post(route('admin.competitions.level.finish', $this->level));

        $response->assertRedirect();
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.level_finish_still_active'),
            session()->get('messages')[0]['message']
        );
        Bus::assertNotDispatched(FinishLevelJob::class);
    }
} 