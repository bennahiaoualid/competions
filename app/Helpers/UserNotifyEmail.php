<?php

namespace App\Helpers;


use App\Models\Admin\Admin;
use App\Jobs\SendBulkEmailJob;
use App\Mail\UserNotification;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Competition\Competition;

class UserNotifyEmail
{
    public static function usersNewCompetition(Competition $competition): void
    {

        $competitionData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'type' => 'new_competition',
            'object' => 'competition',
            'link' => route('competitions.detail', ['slug' => $competition->slug])
        ];

        // Process users in chunks to avoid memory issues and too many individual jobs
        $competition->users->chunk(100)->each(function ($usersChunk) use ($competitionData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();

            Log::info('Sending bulk email job', [
                'userEmailsWithNames' => $userEmailsWithNames,
                'commonEmailData' => $competitionData,
                'locale' => 'ar',
                'usePersonalization' => false
            ]);
            // Use BCC for bulk user notifications (more efficient)
            SendBulkEmailJob::dispatch($userEmailsWithNames, $competitionData, 'ar', false)->onQueue('emails');
        });
    }

    public static function usersUpdateCompetition(Competition $competition): void
    {
        // Eager load users if not already loaded
        // $competition->loadMissing('users');

        $commonData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'type' => 'update_competition',
            'object' => 'competition',
            'link' => route('competitions.detail', ['slug' => $competition->slug])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();
            Log::info('Sending bulk email job', [
                'userEmailsWithNames' => $userEmailsWithNames,
                'commonEmailData' => $commonData,
                'locale' => 'ar',
                'usePersonalization' => false
            ]);
            // Use BCC for bulk user notifications (more efficient)
            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar', false)->onQueue('emails');
        });
    }

    public static function usersActivateCompetition(Competition $competition): void
    {
        // Eager load users if not already loaded
        // $competition->loadMissing('users');

        $commonData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'type' => 'activate_competition',
            'object' => 'competition',
            'link' => route('competitions.detail', ['slug' => $competition->slug])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();
            // Use BCC for bulk user notifications (more efficient)
            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar', false)->onQueue('emails');
        });
    }

    public static function adminLevel(Level $level): void
    {
        $data = [
            'subject' => 'notify',
            'user' => $level->admin->name,
            'competition' => $level->competition->title,
            'level' => $level->name,
            'type' => 'admin_level',
            'object'=> 'level',
            'link' => route('admin.competitions.level.edit',['id'=>base64_encode($level->id)])
        ];
        // Use personalized email for individual admin notifications
        Mail::to($level->admin->email)->queue(new UserNotification($data,'ar'));
    }

    public static function usersUpdateLevel(Competition $competition, Level $level): void
    {
        // Eager load users if not already loaded
        // $competition->loadMissing('users');

        $commonData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'level' => $level->name,
            'type' => 'update_level',
            'object' => 'level',
            'link' => route('competitions.level', ['level' => $level])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();

            // Use BCC for bulk user notifications (more efficient)
            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar', false)->onQueue('emails');
        });
    }

    public static function usersActivateLevel(Competition $competition, Level $level): void
    {
        // Eager load users if not already loaded
        // $competition->loadMissing('users');

        $commonData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'level' => $level->name,
            'type' => 'activate_level',
            'object' => 'level',
            'link' => route('competitions.level', ['level' => $level])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();
    
            // Use BCC for bulk user notifications (more efficient)
            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar', false)->onQueue('emails');
        });
    }

    public static function auditorsfinishLevel(Competition $competition, Level $level): void
    {

        $commonData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'level' => $level->name,
            'type' => 'finish_level',
            'object' => 'level',
            'link' => route('admin.competitions.level.edit', ['id' => base64_encode($level->id)])
        ];
        // Assuming $competition->auditors is a collection of Admin-like objects with email and name
        $competition->auditors->chunk(100)->each(function ($auditorsChunk) use ($commonData) {
            $auditorEmailsWithNames = $auditorsChunk->mapWithKeys(function ($auditor) {
                return [$auditor->email => $auditor->name];
            })->all();
            // Use BCC for bulk auditor notifications (more efficient)
            SendBulkEmailJob::dispatch($auditorEmailsWithNames, $commonData, 'ar', false)->onQueue('emails');
        });
    }

    public static function auditorNewCompetition(Competition $competition, array $ids): void
    {
        $auditors = Admin::whereIn('id', $ids)->get(); // Fetches Admin models

        $commonData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'type' => 'new_auditor',
            'object' => 'competition',
            'link' => route('admin.competitions.edit', ['id' => base64_encode($competition->id)])
        ];

        // Chunk the fetched auditors collection
        collect($auditors)->chunk(100)->each(function ($auditorsChunk) use ($commonData) {
            $auditorEmailsWithNames = $auditorsChunk->mapWithKeys(function ($auditor) {
                return [$auditor->email => $auditor->name]; // Assumes Admin model has email and name
            })->all();
            // Use BCC for bulk auditor notifications (more efficient)
            SendBulkEmailJob::dispatch($auditorEmailsWithNames, $commonData, 'ar', false)->onQueue('emails');
        });
    }
}

