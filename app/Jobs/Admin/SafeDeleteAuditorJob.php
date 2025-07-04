<?php

namespace App\Jobs\Admin;

use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Competition\Competition;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SafeDeleteAuditorJob implements ShouldQueue
{
    use Queueable;
    private Admin $auditor;

    /**
     * Create a new job instance.
     */
    public function __construct(Admin $auditor)
    {
        $this->auditor = $auditor;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $competitions = $this->getCompetitionsToProcess();
        foreach ($competitions as $competition) {
            $this->processCompetition($competition);
        }
        $this->removeAuditorFromCompetitions();
    }

    private function getCompetitionsToProcess()
    {
        return Competition::whereHas('auditors', function ($query) {
            $query->where('admins.id', $this->auditor->id);
        })->where('status', Competition::STATUS_ACTIVE)->get();
    }

    private function processCompetition(Competition $competition): void
    {
        $auditors = DB::table('admin_competition')
            ->where('competition_id', $competition->id)
            ->where('admin_id', '!=', $this->auditor->id)
            ->pluck('admin_id')
            ->toArray();

        if (empty($auditors)) {
            return;
        }

        $randomAuditorId = $auditors[array_rand($auditors)];

        DB::table('level_admin_user')
            ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
            ->where('level_admin_user.admin_id', $this->auditor->id)
            ->where('levels.competition_id', $competition->id)
            ->update(['level_admin_user.admin_id' => $randomAuditorId]);
    }

    private function removeAuditorFromCompetitions(): void
    {
        DB::table('admin_competition')
            ->join('competitions', 'competitions.id', '=', 'admin_competition.competition_id')
            ->where('competitions.status', '!=', '2')
            ->where('admin_competition.admin_id', $this->auditor->id)
            ->delete();
    }
}
