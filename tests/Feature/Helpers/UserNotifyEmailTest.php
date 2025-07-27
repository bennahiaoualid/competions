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

class UserNotifyEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake the queue and mail for testing
        Queue::fake();
        Mail::fake();
    }

    public function test_sends_bulk_emails_for_new_competition()
    {
        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Test Competition',
            'status' => 'pending'
        ]);

        // Create test users
        $users = User::factory()->count(5)->create();
        $competition->users()->attach($users->pluck('id'));

        // Act
        UserNotifyEmail::usersNewCompetition($competition);

        // Assert
        Queue::assertPushed(SendBulkEmailJob::class, function ($job) use ($users) {
            // Check that the job was dispatched with correct data
            $reflection = new \ReflectionClass($job);
            $userEmailsWithNamesProperty = $reflection->getProperty('userEmailsWithNames');
            $userEmailsWithNamesProperty->setAccessible(true);
            $userEmailsWithNames = $userEmailsWithNamesProperty->getValue($job);

            // Check that all users are included
            foreach ($users as $user) {
                $this->assertArrayHasKey($user->email, $userEmailsWithNames);
                $this->assertEquals($user->name, $userEmailsWithNames[$user->email]);
            }

            // Check that usePersonalization is false (BCC mode)
            $usePersonalizationProperty = $reflection->getProperty('usePersonalization');
            $usePersonalizationProperty->setAccessible(true);
            $usePersonalization = $usePersonalizationProperty->getValue($job);
            $this->assertFalse($usePersonalization);

            return true;
        });
    }

    public function test_sends_personalized_email_for_admin_level()
    {
        // Create test data
        $admin = Admin::factory()->create([
            'name' => 'John Admin',
            'email' => 'john@example.com'
        ]);

        $competition = Competition::factory()->create([
            'title' => 'Test Competition'
        ]);

        $level = Level::factory()->create([
            'name' => 'Level 1',
            'admin_id' => $admin->id,
            'competition_id' => $competition->id
        ]);

        // Act
        UserNotifyEmail::adminLevel($level);

        // Assert
        Mail::assertQueued(UserNotification::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_sends_bulk_emails_for_competition_update()
    {
        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Updated Competition'
        ]);

        $users = User::factory()->count(3)->create();
        $competition->users()->attach($users->pluck('id'));

        // Act
        UserNotifyEmail::usersUpdateCompetition($competition);

        // Assert
        Queue::assertPushed(SendBulkEmailJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $commonDataProperty = $reflection->getProperty('commonEmailData');
            $commonDataProperty->setAccessible(true);
            $commonData = $commonDataProperty->getValue($job);

            $this->assertEquals('update_competition', $commonData['type']);
            $this->assertEquals('Updated Competition', $commonData['competition']);

            return true;
        });
    }

    public function test_sends_bulk_emails_for_level_activation()
    {
        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Test Competition'
        ]);

        $level = Level::factory()->create([
            'name' => 'Level 1',
            'competition_id' => $competition->id
        ]);

        $users = User::factory()->count(4)->create();
        $competition->users()->attach($users->pluck('id'));

        // Act
        UserNotifyEmail::usersActivateLevel($competition, $level);

        // Assert
        Queue::assertPushed(SendBulkEmailJob::class, function ($job) use ($level) {
            $reflection = new \ReflectionClass($job);
            $commonDataProperty = $reflection->getProperty('commonEmailData');
            $commonDataProperty->setAccessible(true);
            $commonData = $commonDataProperty->getValue($job);

            $this->assertEquals('activate_level', $commonData['type']);
            $this->assertEquals($level->name, $commonData['level']);

            return true;
        });
    }

    public function test_sends_bulk_emails_for_auditor_notifications()
    {
        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Test Competition'
        ]);

        $auditors = Admin::factory()->count(3)->create();
        $competition->auditors()->attach($auditors->pluck('id'));

        $level = Level::factory()->create([
            'name' => 'Level 1',
            'competition_id' => $competition->id
        ]);

        // Act
        UserNotifyEmail::auditorsfinishLevel($competition, $level);

        // Assert
        Queue::assertPushed(SendBulkEmailJob::class, function ($job) use ($auditors) {
            $reflection = new \ReflectionClass($job);
            $userEmailsWithNamesProperty = $reflection->getProperty('userEmailsWithNames');
            $userEmailsWithNamesProperty->setAccessible(true);
            $userEmailsWithNames = $userEmailsWithNamesProperty->getValue($job);

            // Check that all auditors are included
            foreach ($auditors as $auditor) {
                $this->assertArrayHasKey($auditor->email, $userEmailsWithNames);
                $this->assertEquals($auditor->name, $userEmailsWithNames[$auditor->email]);
            }

            return true;
        });
    }

    public function test_sends_bulk_emails_for_new_auditor_assignment()
    {
        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Test Competition'
        ]);

        $auditors = Admin::factory()->count(2)->create();
        $auditorIds = $auditors->pluck('id')->toArray();

        // Act
        UserNotifyEmail::auditorNewCompetition($competition, $auditorIds);

        // Assert
        Queue::assertPushed(SendBulkEmailJob::class, function ($job) use ($auditors) {
            $reflection = new \ReflectionClass($job);
            $userEmailsWithNamesProperty = $reflection->getProperty('userEmailsWithNames');
            $userEmailsWithNamesProperty->setAccessible(true);
            $userEmailsWithNames = $userEmailsWithNamesProperty->getValue($job);

            // Check that all auditors are included
            foreach ($auditors as $auditor) {
                $this->assertArrayHasKey($auditor->email, $userEmailsWithNames);
                $this->assertEquals($auditor->name, $userEmailsWithNames[$auditor->email]);
            }

            return true;
        });
    }

    public function test_handles_large_number_of_users_with_chunking()
    {
        // Create test data with many users
        $competition = Competition::factory()->create([
            'title' => 'Large Competition'
        ]);

        $users = User::factory()->count(250)->create();
        $competition->users()->attach($users->pluck('id'));

        // Act
        UserNotifyEmail::usersNewCompetition($competition);

        // Assert - should create 3 jobs (250 users / 100 chunk size = 3 chunks)
        Queue::assertPushed(SendBulkEmailJob::class, 3);
    }

    public function test_uses_correct_locale_for_emails()
    {
        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Test Competition'
        ]);

        $users = User::factory()->count(2)->create();
        $competition->users()->attach($users->pluck('id'));

        // Act
        UserNotifyEmail::usersNewCompetition($competition);

        // Assert
        Queue::assertPushed(SendBulkEmailJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $localeProperty = $reflection->getProperty('locale');
            $localeProperty->setAccessible(true);
            $locale = $localeProperty->getValue($job);

            $this->assertEquals('ar', $locale);

            return true;
        });
    }

    public function test_queues_emails_on_correct_queue()
    {
        // Create test data
        $competition = Competition::factory()->create([
            'title' => 'Test Competition'
        ]);

        $users = User::factory()->count(2)->create();
        $competition->users()->attach($users->pluck('id'));

        // Act
        UserNotifyEmail::usersNewCompetition($competition);

        // Assert
        Queue::assertPushedOn('emails', SendBulkEmailJob::class);
    }
} 