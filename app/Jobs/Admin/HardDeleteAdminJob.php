<?php

namespace App\Jobs\Admin;

use Auth;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Competition\Response;
use App\Models\Competition\Competition;
use App\Exceptions\UserFriendlyException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\Monitoring\DeletionRequested;
use App\Models\Monitoring\DeletionRequest;

/**
 * Handles the hard deletion of an admin, permanently removing them from the system.
 * 
 * This job performs a hard delete operation on an admin that has already been soft deleted.
 * The process includes:
 * - Validating that the admin is already soft deleted
 * - Deleting suspended competitions owned by the admin
 * - Transferring ownership of finished competitions to a transfer admin
 * - Transferring users and admins created by the admin to the transfer admin
 * - Reassigning audited responses to the transfer admin
 * - Permanently deleting the admin
 * - Updating the deletion request status
 * 
 * @package App\Jobs\Admin
 */
class HardDeleteAdminJob implements ShouldQueue
{
    use Queueable;

    private Admin $admin;
    protected int $initiatorId;
    private DeletionRequest $deletionRequest;
    private ?Admin $transferAdmin;

    /**
     * Create a new hard delete admin job instance.
     *
     * @param Admin $admin The admin to be hard deleted (must be soft deleted first)
     * @param int $initiatorId The ID of the admin initiating the hard deletion
     * @param DeletionRequest $deletionRequest The deletion request record
     * @param Admin|null $transferAdmin The admin to transfer ownership to (defaults to authenticated user)
     */
    public function __construct(Admin $admin, int $initiatorId, DeletionRequest $deletionRequest, ?Admin $transferAdmin = null)
    {
        $this->admin = $admin;
        $this->initiatorId = $initiatorId;
        $this->deletionRequest = $deletionRequest;
        $this->transferAdmin = $transferAdmin ?? Auth::user();
    }

    /**
     * Execute the hard delete job.
     *
     * This method performs the complete hard deletion process:
     * 1. Validates that the admin is soft deleted
     * 2. Deletes suspended competitions owned by the admin
     * 3. Transfers ownership of finished competitions to transfer admin
     * 4. Transfers users and admins created by the admin to transfer admin
     * 5. Reassigns audited responses to transfer admin
     * 6. Permanently deletes the admin
     * 7. Updates the deletion request status
     *
     * @return void
     * @throws UserFriendlyException If the admin is not soft deleted
     * @throws \Exception If any step in the process fails
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Ensure the admin is soft deleted
            if (!$this->admin->trashed()) {
                throw new UserFriendlyException(
                    translationKey: 'job.errors.admin_must_soft_deleted',
                    message: 'Cannot delete admin: Admin must be soft deleted before hard delete.'
                );
            
            }

            // 1. Delete suspended competitions owned by admin
            Competition::where('admin_id', $this->admin->id)
                ->where('is_suspended', true)
                ->forceDelete();

            // 2. Transfer ownership of finished competitions
            Competition::where('admin_id', $this->admin->id)
                ->where('status', Competition::STATUS_COMPLETED)
                ->update(['admin_id' => $this->transferAdmin?->id]);

            // 3. Transfer users/admins created by this admin
            User::where('admin_id', $this->admin->id)
                ->update(['admin_id' => $this->transferAdmin?->id]);
            Admin::where('admin_id', $this->admin->id)
                ->update(['admin_id' => $this->transferAdmin?->id]);

            // 4. Reassign audited responses
            Response::where('admin_id', $this->admin->id)
                ->update(['admin_id' => $this->transferAdmin?->id]);

            // 5. Remove from other related tables as needed (add here if needed)

            // 6. Permanently delete the admin
            $this->admin->forceDelete();

            DB::afterCommit(function () {
                $initiatorAdmin = Admin::find($this->initiatorId);
                $this->deletionRequest->update([
                    'status' => 'approved',
                    'approved_by_admin_id' => $initiatorAdmin->id,
                    'snapshot_approver_name' => $initiatorAdmin->name . ' - ' . $initiatorAdmin->email,
                    'approved_at' => now(),
                ]);
            });
        });
    }
}
