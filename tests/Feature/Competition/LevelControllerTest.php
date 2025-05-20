<?php

namespace Tests\Feature\Competition;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\User;
use App\Services\Competition\LevelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class LevelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Optionally seed or create users/admins/competitions here
    }

    public function test_store_successful()
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $competition = Competition::factory()->create([
            'admin_id' => $admin->id,
            'status' => 0 
        ]); 

        // Log IDs for debugging
        // \Illuminate\Support\Facades\Log::info('[Test] Acting as Admin ID: ' . $admin->id);
        // \Illuminate\Support\Facades\Log::info('[Test] Auth Guard Admin ID: ' . Auth::guard('admin')->id());
        // \Illuminate\Support\Facades\Log::info('[Test] Competition ID: ' . $competition->id . ' Owner Admin ID: ' . $competition->admin_id);

        $mockService = Mockery::mock(LevelService::class);
        $mockService->shouldReceive('create')->once()->andReturn([
            'status' => 'success',
            'level' => null, 
            'message_key' => 'saved'
        ]);
        $this->app->instance(LevelService::class, $mockService);

        $response = $this->post(route('admin.competitions.level.store'), [
            'competition_id' => $competition->id,
            'name' => 'Level 1',
            'description' => 'Test Level Description',
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'duration' => 60,
            'questions_number' => 10,
            'admin_id' => $admin->id,
        ]);

        // dd(session()->all()); // Temporary dump

        $response->assertRedirect();
        $response->assertSessionHas('messages');
        $this->assertTrue(Session::has('messages'));
    }

    public function test_store_validation_error()
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $competitionOwnedByAdmin = Competition::factory()->create([
            'admin_id' => $admin->id,
            'status' => 0 // Ensure competition is inactive
        ]);

        // Missing required fields
        $response = $this->post(route('admin.competitions.level.store'), [
            'competition_id' => $competitionOwnedByAdmin->id, // Use a valid competition ID
            'name' => '',
            'start_date' => '',
            'duration' => '',
            // questions_number is missing to test required
            'admin_id' => '', // This should cause a validation error for required/exists
        ]);

        $response->assertSessionHasErrors([
            // 'competition_id', // Should not have an error now
            'name',
            'start_date',
            'duration',
            'questions_number',
            'admin_id'
        ], null, 'createLevel');
    }

    public function test_store_service_returns_error()
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $competition = Competition::factory()->create([
            'admin_id' => $admin->id,
            'status' => 0 // Ensure competition is inactive
        ]);

        $mockService = Mockery::mock(LevelService::class);
        $mockService->shouldReceive('create')->once()->andReturn([
            'status' => 'error',
            'message_key' => 'messages.validation.not_allow.competition_max_levels'
        ]);
        $this->app->instance(LevelService::class, $mockService);

        $response = $this->post(route('admin.competitions.level.store'), [
            'competition_id' => $competition->id,
            'name' => 'Level Service Error Test',
            'description' => 'A valid description for testing service error.',
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'duration' => 60,
            'questions_number' => 10,
            'admin_id' => $admin->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('messages');
        $this->assertTrue(Session::has('messages'));
    }
}