<?php
namespace App\Services\Monitoring\DeletionRequest;

use App\Models\User;
use App\Models\Admin\Admin;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\DeletionRequest;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Monitoring\DeletionRequests\RestorableHandlerInterface;

class UserRestoreHandler implements RestorableHandlerInterface
{
    public function __construct(
        private User $user,
        private DeletionRequest $deletionRequest,
        private TransactionManagerInterface $tx,
        private Admin $initiator,
        private ?FlasherInterface $flasher = null
    ) {}

    public function restore(): bool
    {
        return $this->tx->run(function () {
            $this->user->restore();

            $this->deletionRequest->update([
                'status' => 'rejected',
                'approved_by_admin_id' => $this->initiator->id,
                'snapshot_approver_name' => $this->initiator->name . ' - ' . $this->initiator->email,
                'approved_at' => now(),
            ]);

            if ($this->flasher) {
                $this->flasher->crudSuccess('restored');
            }

            return true;
        });
    }
}

