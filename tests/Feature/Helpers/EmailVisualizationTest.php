<?php

namespace Tests\Feature\Helpers;

use App\Helpers\UserNotifyEmail;
use App\Jobs\SendBulkEmailJob;
use App\Mail\UserNotification;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailVisualizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Don't fake mail for this test - we want to see the actual emails
        // Mail::fake();
        Queue::fake();
        
        // Configure mail to use log driver for testing (optional)
        config(['mail.default' => 'log']);
    }

    
    public function test_can_send_new_competition_email_to_mailtrap()
    {
        // Set mail driver to smtp for Mailtrap
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
                'port' => env('MAIL_PORT', 2525),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
            ]
        ]);

        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Test Competition for Mailtrap',
            'description' => 'This competition was created for email testing'
        ]);

        // Create test user with your test email
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com' // Replace with your actual test email
        ]);

        $competition->users()->attach($testUser->id);

        // Instead of using the helper (which dispatches jobs), send directly
        $emailData = [
            'subject' => 'New Competition Available',
            'competition' => $competition->title,
            'user' => $testUser->name,
            'type' => 'new_competition',
            'object' => 'competition',
            'link' => route('competitions.detail', ['competition' => $competition])
        ];

        // Send email directly to see it immediately in Mailtrap
        Mail::to($testUser->email)->send(new UserNotification($emailData, 'ar'));

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    
    public function test_can_send_admin_level_email_to_mailtrap()
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
                'port' => env('MAIL_PORT', 2525),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
            ]
        ]);

        // Create test data
        $admin = Admin::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin-test@example.com' // Replace with your test email
        ]);

        $competition = Competition::factory()->create([
            'title' => 'Admin Test Competition'
        ]);

        $level = Level::factory()->create([
            'name' => 'Test Level for Admin',
            'admin_id' => $admin->id,
            'competition_id' => $competition->id
        ]);

        // Send admin level email directly
        $emailData = [
            'subject' => 'Level Assignment Notification',
            'user' => $admin->name,
            'competition' => $competition->title,
            'level' => $level->name,
            'type' => 'admin_level',
            'object' => 'level',
            'link' => route('admin.competitions.level.edit', ['id' => base64_encode($level->id)])
        ];

        Mail::to($admin->email)->send(new UserNotification($emailData, 'ar'));

        $this->assertTrue(true);
    }

    
    public function test_can_send_bulk_emails_with_different_modes()
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
                'port' => env('MAIL_PORT', 2525),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
            ]
        ]);

        $userEmailsWithNames = [
            'user1@example.com' => 'Test User 1',
            'user2@example.com' => 'Test User 2',
            'user3@example.com' => 'Test User 3'
        ];

        $commonData = [
            'subject' => 'Bulk Email Test',
            'competition' => 'Test Competition for Bulk Email',
            'type' => 'test_bulk',
            'object' => 'test',
            'link' => 'https://example.com/test'
        ];

        // Create job instance and execute directly
        $bccJob = new SendBulkEmailJob($userEmailsWithNames, $commonData, 'ar', false);
        $bccJob->handle();

        // Create personalized job and execute
        $personalizedJob = new SendBulkEmailJob($userEmailsWithNames, $commonData, 'ar', true);
        $personalizedJob->handle();

        $this->assertTrue(true);
    }

}