<?php

namespace App\Helpers;


use App\Mail\UserNotification;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendBulkEmailJob;

class UserNotifyEmail
{
    public static function usersNewCompetition(Competition $competition): void
    {
        // Eager load users if not already loaded (best done when $competition is fetched)
        // $competition->loadMissing('users');

        $competitionData = [
            'subject' => 'notify',
            'competition' => $competition->title,
            'type' => 'new_competition',
            'object' => 'competition',
            'link' => route('competitions.detail', ['id' => base64_encode($competition->id)])
        ];

        // Process users in chunks to avoid memory issues and too many individual jobs
        $competition->users->chunk(100)->each(function ($usersChunk) use ($competitionData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();

            // Instead of Mail::to()->queue() for each user,
            // dispatch a single job for the chunk.
            // This job would then iterate and send or use BCC.
            SendBulkEmailJob::dispatch($userEmailsWithNames, $competitionData, 'ar')->onQueue('emails');
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
            'link' => route('competitions.detail', ['id' => base64_encode($competition->id)])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();
            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar')->onQueue('emails');
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
            'link' => route('competitions.detail', ['id' => base64_encode($competition->id)])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();
            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar')->onQueue('emails');
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
            'link' => route('competitions.level', ['id' => base64_encode($level->id)])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();

            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar')->onQueue('emails');
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
            'link' => route('competitions.level', ['id' => base64_encode($level->id)])
        ];

        $competition->users->chunk(100)->each(function ($usersChunk) use ($commonData) {
            $userEmailsWithNames = $usersChunk->mapWithKeys(function ($user) {
                return [$user->email => $user->name];
            })->all();
    
            SendBulkEmailJob::dispatch($userEmailsWithNames, $commonData, 'ar')->onQueue('emails');
        });
    }

    public static function auditorsfinishLevel(Competition $competition, Level $level): void
    {
        // Eager load auditors if not already loaded
        // $competition->loadMissing('auditors');

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
            SendBulkEmailJob::dispatch($auditorEmailsWithNames, $commonData, 'ar')->onQueue('emails');
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
            SendBulkEmailJob::dispatch($auditorEmailsWithNames, $commonData, 'ar')->onQueue('emails');
        });
    }
}

