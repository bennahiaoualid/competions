<?php

namespace App\Services\Admin;

use Exception;
use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use App\Traits\RoleManipulation;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Jobs\Admin\DeleteAdminCoordinatorJob;
use App\Contracts\TransactionManagerInterface;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use App\Traits\CrudOperationNotificationAlert;
use App\Services\Monitoring\JobTrackingService;
use App\Interface\Admin\AdminRepositoryInterface;
use Egulias\EmailValidator\Result\Reason\Reason;

class AdminService
{
    use CrudOperationNotificationAlert, RoleManipulation, RegisterLogs;

    public function __construct(
        protected AdminRepositoryInterface $adminRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher,
        protected JobTrackingService $jobTrackingService,
    ) {
    }

    /**
     * Show dashboard with admin/user counts.
     * Business logic .
     */
    public function index(): array
    {
        $count = [
            'admin' => $this->adminRepository->getAdminCount(),
            'user' => $this->adminRepository->getUserCount(),
        ];
        return ['count' => $count];
    }

    /**
     * Show admin list with possible roles.
     * Business logic.
     * @return array roles
     */
    public function adminList(): array
    {
        $roles = $this->possibleRoles();
        return ['roles' => $roles];
    }

    /**
     * Create an admin with transaction and notification.
     */
    public function create(array $data): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($data) {

                $admin = $this->adminRepository->create(array_merge($data, ['admin_id' => Auth::id()]));
                $admin->roles()->sync($data['role']);
                return true;
            });
            $this->flasher->crudSuccess('saved');
            return $result;
        } catch (\Exception $exception) {
            $this->registerLogs('Admin creation error: ', $exception);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Show edit admin form with possible roles.
     * Business logic.
     */
    public function edit(Admin $admin): array
    {
        $roles = $this->possibleRoles();
        return ['admin' => $admin, 'roles' => $roles];
    }

    /**
     * Update an admin with transaction and notification.
     */
    public function update(Admin $admin, array $data): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($admin, $data) {
                $admin = $this->adminRepository->update($admin, [
                    'name' => $data['name'],
                    'email' => $data['email'],
                ]);
                $admin->roles()->sync($data['role']);
                return true;
            });
            $this->flasher->crudSuccess('updated');
            return $result;
        } catch (\Exception $exception) {
            $this->registerLogs('Admin updating error: ', $exception);
            $this->flasher->crudFailure('updated');
            return false;
        }
    }

    /**
     * Delete an admin with transaction and notification.
     */
    public function delete(Admin $admin, string $reason): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($admin, $reason) {

                $job = $this->createDeleteJob($admin, $reason);

                $this->jobTrackingService->dispatchWithTracking($job);

                return true;
            });
            $this->flasher->info('deleted');
            return $result;
        } catch (\Exception $exception) {
            $this->registerLogs('Admin deleting error: ', $exception);
            $this->flasher->info(__('messages.validation.info.deleted'));
            return false;
        }
    }

    protected function createDeleteJob(Admin $admin, string $reason): DeleteAdminCoordinatorJob
    {
        return new DeleteAdminCoordinatorJob(
            admin: $admin,
            mode: 'soft',
            initiatorId: Auth::id(),
            skipTrackingCreation:false,
            reason: $reason
        );
    }
}
