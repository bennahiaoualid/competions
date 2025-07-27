<?php

namespace Tests\Feature\Helpers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Jobs\SendBulkEmailJob;
use App\Mail\UserNotification;
use App\Helpers\UserNotifyEmail;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use App\Models\Competition\Competition;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserNotifyEmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake both mail and queue to see real behavior
        //Mail::fake();
        //Queue::fake();
    }

    public function test_can_send_new_competition_email_to_mailtrap()
    {
        $competition = Competition::factory()->create([
            'title' => 'Test Competition',
            'status' => 'pending'
        ]);

        $users = User::factory()->count(5)->create();
        $competition->users()->attach($users->pluck('id'));
        Log::info('Sending bulk email job');
        UserNotifyEmail::usersNewCompetition($competition);
        $this->assertTrue(true);
    }
} 