<?php

namespace App\Jobs\Admin;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SoftDeleteAdminJob implements ShouldQueue
{
    use Queueable;
    protected Admin $admin;

    /**
     * Create a new job instance.
     */
    public function __construct(Admin $admin)
    {
        $this->admin = $admin;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->suspendUnfinishedCompetitions();
        $this->admin->delete();
    }

    private function suspendUnfinishedCompetitions()
    {
        $this->admin->competitions()->where('status', '!=', Competition::STATUS_COMPLETED)
                    ->update(['is_suspended' => true]);
    }
}
