<?php
namespace App\Services\Monitoring\DeletionRequest;

use Exception;
use RuntimeException;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\DeletionRequest;
use App\Jobs\Admin\DeleteAdminCoordinatorJob;
use App\Services\Monitoring\JobTrackingService;
use App\Interface\Monitoring\DeletionRequests\HardDeleteHandlerInterface;
use App\Traits\RegisterLogs;

/**
 * Handler for hard deleting admin entities.
 * 
 * This handler is responsible for orchestrating the hard deletion of admin entities.
 * It validates the transfer admin (if provided), creates the appropriate coordinator job,
 * and dispatches it with tracking. The handler ensures that only admins with proper
 * roles can be used as transfer admins.
 * 
 * @package App\Services\Monitoring\DeletionRequest
 */
class AdminHardDeleteHandler implements HardDeleteHandlerInterface
{
    use RegisterLogs;
    /**
     * Create a new admin hard delete handler instance.
     *
     * @param Admin $admin The admin to be hard deleted
     * @param DeletionRequest $deletionRequest The deletion request record
     * @param Admin $admin_auth The authenticated admin performing the deletion
     * @param JobTrackingService $jobService Service for tracking job execution
     * @param FlasherInterface $flasher Service for displaying flash messages to users
     * @param Request|null $request The HTTP request containing additional parameters
     */
    public function __construct(
        private Admin $admin,
        private DeletionRequest $deletionRequest,
        private Admin $admin_auth,
        private JobTrackingService $jobService,
        private FlasherInterface $flasher,
        private ?Request $request = null
    ) {}

    /**
     * Execute the hard delete operation for an admin.
     *
     * This method performs the hard deletion of an admin by:
     * 1. Validating the transfer admin (if provided in the request)
     * 2. Ensuring the transfer admin has the required roles (super_admin or owner)
     * 3. Creating a coordinator job to handle the deletion process
     * 4. Dispatching the job with tracking
     * 5. Providing user feedback through flash messages
     *
     * @return bool True if the deletion job was successfully dispatched
     * @throws \Exception If the transfer admin is invalid or doesn't have required roles
     */
    public function delete(): bool
    {
        $newAdmin = null;
        $data_validated = $this->request && $this->request->admin_id;
        if ($data_validated) {
            $newAdmin = Admin::availableAsOwnershipTransfer()->where('id', $this->request->admin_id)->first();
        }

        if (!$newAdmin) {
            $exp = new Exception('The provided admin_id is invalid or does not have the required role.');
            $this->registerLogs('AdminHardDeleteHandler::delete',$exp);
            throw $exp;
        }

        
        $job = new DeleteAdminCoordinatorJob(
            admin: $this->admin,
            mode: 'hard',
            initiatorId: Auth::id(),
            skipTrackingCreation:false,
            reason: null,
            transferAdmin: $newAdmin,
            deletionRequest:$this->deletionRequest
        );
        $this->jobService->dispatchWithTracking($job);
        $this->flasher->info(__('messages.validation.info.deleted'));
        return true;
    }
}
