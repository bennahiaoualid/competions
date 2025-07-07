<?php

namespace App\Jobs\Admin;

use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\Monitoring\DeletionRequested;

class SoftDeleteAdminJob implements ShouldQueue
{

    protected Admin $admin;
    protected ?int $userId;
    protected string $reason;

    public function __construct(Admin $admin, ?int $userId, string $reason)
    {
        $this->admin = $admin;
        $this->userId = $userId ?? Auth::id();
        $this->reason = $reason;
    }

    public function handle(): void
    {
        DB::transaction(function () {
        
            $this->suspendUnfinishedCompetitions();

            $this->admin->delete();

            DB::afterCommit(function () {
                $requestedBy = Admin::find($this->userId);

                event(new DeletionRequested(
                    $this->admin,
                    $requestedBy,
                    $this->reason,
                    $this->admin->name . ' - ' . $this->admin->email
                ));
            });
        });
    }

    private function suspendUnfinishedCompetitions(): void
    {
        $this->admin->competitions()
            ->where('status', '!=', Competition::STATUS_COMPLETED)
            ->update(['is_suspended' => true]);
    }
}