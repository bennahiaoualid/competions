<?php

namespace App\Helpers;

use App\Models\Competition\Competition;
use Exception;
use Illuminate\Support\Facades\DB;

class AuditorSaveDelete
{
    /*
        * Delete an auditor from a competition
        * @param int $auditor_id The ID of the auditor to delete
        * @param Competition|null $competition The competition to delete the auditor from
        * @return bool True if the auditor was deleted successfully, false otherwise
    */
    public static function deleteAuditor($auditor_id, $competition = null): bool
    {
        DB::beginTransaction();

        try {
            // Step 1: Determine the competitions to process
            // If a specific competition is provided, use it; otherwise, find all competitions where the admin is an auditor

            if ($competition != null) {
                $competitions = collect([$competition]);
            } else {
                $competitions = Competition::whereHas('auditors' , function ($q) use ($auditor_id) {
                    $q->where('admins.id' , $auditor_id);
                })->where('status' , "1")->get();
            }
            // Step 2: Loop through each competition and update records
            foreach ($competitions as $comp) {
                // Get all auditors in the competition except the one being deleted
                $auditors = DB::table('admin_competition')
                    ->where('competition_id', $comp->id)
                    ->where('admin_id', '!=', $auditor_id)
                    ->pluck('admin_id')
                    ->toArray();

                if (empty($auditors)) {
                    continue; // No other auditors available for reassignment
                }

                // Select a random auditor from the remaining auditors
                $randomAuditorId = $auditors[array_rand($auditors)];

                // Update all records in level_admin_user with the selected random auditor
                DB::table('level_admin_user')
                    ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
                    ->where('level_admin_user.admin_id', $auditor_id)
                    ->where('levels.competition_id', $comp->id)
                    ->update(['level_admin_user.admin_id' => $randomAuditorId]);
            }
            // remove the admin from being auditor in all upcoming and running competitions
            if ($competition == null) {
                DB::table('admin_competition')
                    ->join('competitions', 'competitions.id', '=', 'admin_competition.competition_id')
                    ->where('competitions.status', '!=',"2")
                    ->where('admin_competition.admin_id', $auditor_id)
                    ->delete();
            }
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
            return false;
        }
    }

}

