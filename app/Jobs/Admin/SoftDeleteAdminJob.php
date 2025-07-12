<?php

namespace App\Jobs\Admin;

use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use App\Exceptions\UserFriendlyException;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\Monitoring\DeletionRequested;

/**
 * Handles the soft deletion of an admin, including business logic validation and cleanup.
 * 
 * This job performs a soft delete operation on an admin, which includes:
 * - Validating that the admin doesn't own active competitions with running levels
 * - Suspending unfinished competitions owned by the admin
 * - Reassigning levels to competition owners where appropriate
 * - Creating a deletion request for monitoring purposes
 * 
 * @package App\Jobs\Admin
 */
class SoftDeleteAdminJob implements ShouldQueue
{

    protected Admin $admin;
    protected ?int $userId;
    protected string $reason;

    /**
     * Create a new soft delete admin job instance.
     *
     * @param Admin $admin The admin to be soft deleted
     * @param int|null $userId The ID of the user initiating the deletion (defaults to authenticated user)
     * @param string $reason The reason for the deletion
     */
    public function __construct(Admin $admin, ?int $userId, string $reason)
    {
        $this->admin = $admin;
        $this->userId = $userId ?? Auth::id();
        $this->reason = $reason;
    }

    /**
     * Execute the soft delete job.
     *
     * This method orchestrates the complete soft deletion process:
     * 1. Validates business rules (no active competitions with running levels)
     * 2. Suspends unfinished competitions
     * 3. Reassigns levels to competition owners
     * 4. Soft deletes the admin
     * 5. Creates a deletion request for monitoring
     *
     * @return void
     * @throws UserFriendlyException If the admin owns active competitions with running levels
     * @throws \Exception If any step in the process fails
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $this->abortIfOwnsActiveCompetitionWithRunningLevel();
            $this->suspendUnfinishedCompetitions();
            $this->reassignLevelsToCompetitionOwner();
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

    /**
     * Abort the deletion if the admin owns active competitions with running levels.
     *
     * This validation ensures that admins cannot be deleted while they own
     * competitions that have active levels running, as this could disrupt
     * ongoing competitions.
     *
     * @return void
     * @throws UserFriendlyException If the admin owns active competitions with running levels
     */
    private function abortIfOwnsActiveCompetitionWithRunningLevel(): void
    {
        $competitions = Competition::where('admin_id', $this->admin->id)
            ->where('status', Competition::STATUS_ACTIVE)
            ->whereHas('levels', function ($query) {
                $query->where('status', Level::STATUS_ACTIVE);
            })
            ->get();

        if ($competitions->count() > 0) {
            throw new UserFriendlyException(
                translationKey: 'job.errors.owns_active_competition_with_running_level',
                contextData: ['competitions' => $competitions->pluck('title')->toArray()],
                message: 'Cannot delete admin: they own competitions with running levels.'
            );
        }
    }

    /**
     * Suspend all unfinished competitions owned by the admin.
     *
     * This method marks all competitions owned by the admin as suspended
     * if they are not in a completed status. This prevents further activity
     * on these competitions while maintaining data integrity.
     *
     * @return void
     */
    private function suspendUnfinishedCompetitions(): void
    {
        $this->admin->competitions()
            ->where('status', '!=', Competition::STATUS_COMPLETED)
            ->update(['is_suspended' => true]);
    }

    /**
     * Reassign levels to competition owners where the admin is not the competition owner.
     *
     * This method ensures that levels created by the admin are reassigned to
     * the actual competition owners, maintaining proper ownership relationships
     * and preventing orphaned levels.
     *
     * @return void
     */
    private function reassignLevelsToCompetitionOwner(): void
    {
        DB::table('levels')
            ->join('competitions', 'levels.competition_id', '=', 'competitions.id')
            ->where('levels.admin_id', $this->admin->id)
            ->where('competitions.admin_id', '!=', $this->admin->id)
            ->update(['levels.admin_id' => DB::raw('competitions.admin_id')]);
    }
}