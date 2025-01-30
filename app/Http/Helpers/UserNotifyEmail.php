<?php

namespace App\Http\Helpers;


use App\Mail\UserNotification;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\Mail;

class UserNotifyEmail
{
    public static function usersNewCompetition(Competition $competition): void
    {
        foreach ($competition->users as $user) {
            $data = [
                'subject' => 'notify',
                'user' => $user->name,
                'competition' => $competition->title,
                'type' => 'new_competition',
                'object'=> 'competition',
                'link' => route('competitions.detail',['id'=>base64_encode($competition->id)])
            ];
            Mail::to($user->email)->queue(new UserNotification($data,'ar'));
        }
    }

    public static function usersUpdateCompetition(Competition $competition): void
    {
        foreach ($competition->users as $user) {
            $data = [
                'subject' => 'notify',
                'user' => $user->name,
                'competition' => $competition->title,
                'type' => 'update_competition',
                'object'=> 'competition',
                'link' => route('competitions.detail',['id'=>base64_encode($competition->id)])
            ];
            Mail::to($user->email)->queue(new UserNotification($data,'ar'));
        }
    }

    public static function usersActivateCompetition(Competition $competition): void
    {
        foreach ($competition->users as $user) {
            $data = [
                'subject' => 'notify',
                'user' => $user->name,
                'competition' => $competition->title,
                'type' => 'activate_competition',
                'object'=> 'competition',
                'link' => route('competitions.detail',['id'=>base64_encode($competition->id)])
            ];
            Mail::to($user->email)->queue(new UserNotification($data,'ar'));
        }
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
        foreach ($competition->users as $user) {
            $data = [
                'subject' => 'notify',
                'user' => $user->name,
                'competition' => $competition->title,
                'level' => $level->name,
                'type' => 'update_level',
                'object'=> 'level',
                'link' => route('competitions.level',['id'=>base64_encode($level->id)])
            ];
            Mail::to($user->email)->queue(new UserNotification($data,'ar'));
        }
    }

    public static function usersActivateLevel(Competition $competition, Level $level): void
    {
        foreach ($competition->users as $user) {
            $data = [
                'subject' => 'notify',
                'user' => $user->name,
                'competition' => $competition->title,
                'level' => $level->name,
                'type' => 'activate_level',
                'object'=> 'level',
                'link' => route('competitions.level',['id'=>base64_encode($level->id)])
            ];
            Mail::to($user->email)->queue(new UserNotification($data,'ar'));
        }
    }

    public static function auditorsfinishLevel(Competition $competition, Level $level): void
    {
        foreach ($competition->auditors as $user) {
            $data = [
                'subject' => 'notify',
                'user' => $user->name,
                'competition' => $competition->title,
                'level' => $level->name,
                'type' => 'finish_level',
                'object'=> 'level',
                'link' => route('admin.competitions.level.edit',['id'=>base64_encode($level->id)])
            ];
            Mail::to($user->email)->queue(new UserNotification($data,'ar'));
        }
    }

    public static function auditorNewCompetition(Competition $competition,array $ids): void
    {
        $auditors = Admin::whereIn('id',$ids)->get();
        foreach ($auditors as $user) {
            $data = [
                'subject' => 'notify',
                'user' => $user->name,
                'competition' => $competition->title,
                'type' => 'new_auditor',
                'object'=> 'competition',
                'link' => route('admin.competitions.edit',['id'=>base64_encode($competition->id)])
            ];
            Mail::to($user->email)->queue(new UserNotification($data,'ar'));
        }
    }
}

