<?php

namespace App\Helpers;


use App\Models\Competition\Competition;
use Exception;
use Illuminate\Support\Facades\DB;

class UserSafeDelete
{
    public static function deleteUser($user_id): bool
    {
        DB::beginTransaction();

        try {
            // Step 1: Determine the competitions to process
            $competitions = Competition::whereHas('users' , function ($q) use ($user_id) {
                $q->where('users.id' , $user_id);
            })->where('status' , "1")->get();

            // Step 2: Loop through each competition and update records
            foreach ($competitions as $comp) {

                // Update all records in level_admin_user with the selected random auditor
                DB::table('level_admin_user')
                    ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
                    ->where('level_admin_user.user_id', $user_id)
                    ->where('levels.competition_id', $comp->id)
                    ->delete();
            }
            // remove the user from being competitor in all upcoming and running competitions
            DB::table('competition_user')
                ->join('competitions', 'competitions.id', '=', 'competition_user.competition_id')
                ->where('competitions.status', '!=',"2")
                ->where('competition_user.user_id', $user_id)
                ->delete();
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

}

