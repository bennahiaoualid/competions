<?php

namespace App\Services\Monitoring;

use App\Models\User;
use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use Illuminate\Http\Request;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Factories\Monitoring\RestoreHandlerFactory;
use App\Models\Monitoring\DeletionRequest;
use App\Contracts\TransactionManagerInterface;
use App\Factories\Monitoring\HardDeleteHandlerFactory;
use App\Services\Monitoring\JobTrackingService;

/**
 * Service for orchestrating hard delete and restore operations for various entity types.
 * 
 * This service provides a generic interface for hard deleting and restoring entities
 * (admins, users, etc.) by using factory patterns to create appropriate handlers
 * for each entity type. It includes comprehensive error handling and logging.
 * 
 * @package App\Services\Monitoring
 */
class DeletionRecordsService
{
    use RegisterLogs;
    
    /**
     * Create a new deletion records service instance.
     *
     * @param TransactionManagerInterface $transaction_manager Service for managing database transactions
     * @param JobTrackingService $jobTrackingService Service for tracking job execution
     * @param FlasherInterface $flasher Service for displaying flash messages to users
     * @param RestoreHandlerFactory $restore_handler_factory Factory for creating restore handlers
     * @param HardDeleteHandlerFactory $hard_delete_handler_factory Factory for creating hard delete handlers
     */
    public function __construct(
        private TransactionManagerInterface $transaction_manager,
        private JobTrackingService $jobTrackingService,
        private FlasherInterface $flasher,
        private RestoreHandlerFactory $restore_handler_factory,
        private HardDeleteHandlerFactory $hard_delete_handler_factory
    ) {}

    /**
     * Generic hard delete method that dispatches appropriate job based on entity type.
     *
     * This method handles hard deletion of any supported entity type by:
     * 1. Retrieving the deletable entity from the deletion request
     * 2. Creating the appropriate handler using the factory pattern
     * 3. Executing the deletion through the handler
     * 4. Providing comprehensive error handling and user feedback
     *
     * @param DeletionRequest $deletionRequest The deletion request containing entity information
     * @param Request|null $request The HTTP request containing additional parameters (e.g., transfer admin)
     * @return bool True if the deletion was successful, false otherwise
     * @throws \Exception If the deletable entity is not found or if the deletion process fails
     */
    public function hardDelete(DeletionRequest $deletionRequest, ?Request $request): bool
    {
        try {
             /** @var Admin $admin */
            $admin = Auth::user();

            // Get the deletable entity
            $deletable = $deletionRequest->deletable;

            if (!$deletable) {
                throw new \Exception('Deletable entity not found');
            }

            $handler = $this->hard_delete_handler_factory->make($deletable, $deletionRequest, $admin, $request);


            // Create and dispatch the appropriate job
            return $handler->delete($this->jobTrackingService, $this->flasher);

        } catch (\Exception $e) {
            $this->registerLogs('DeletionREcordsService :: hardDelete',$e);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

    /**
     * Generic restore method that handles restoration of soft-deleted entities.
     *
     * This method handles restoration of any supported entity type by:
     * 1. Retrieving the soft-deleted entity from the deletion request
     * 2. Creating the appropriate restore handler using the factory pattern
     * 3. Executing the restoration through the handler
     * 4. Providing comprehensive error handling
     *
     * @param DeletionRequest $deletionRequest The deletion request containing entity information
     * @return bool True if the restoration was successful, false otherwise
     * @throws \Exception If the deletable entity is not found or if the restoration process fails
     */
    public function restore(DeletionRequest $deletionRequest): bool
    {
        try {
            /** @var Admin $admin */
            $admin = Auth::user();
            $deletable = $this->getDeletableWithTrashed($deletionRequest);
            if (!$deletable) {
                throw new \Exception('Deletable entity not found');
            }
    
            $handler = $this->restore_handler_factory->make($deletable, $deletionRequest, $admin);
    
            return $handler->restore();
        } catch (\Exception $e) {
            $this->registerLogs('DeletionRecordsService::restore', $e);
            return false;
        }
    }

    /**
     * Retrieve a soft-deleted entity based on the deletion request.
     *
     * This method uses the deletion request's entity type and ID to retrieve
     * the corresponding soft-deleted entity from the database. It supports
     * multiple entity types through a match expression.
     *
     * @param DeletionRequest $deletionRequest The deletion request containing entity type and ID
     * @return User|Admin|null The soft-deleted entity or null if not found
     */
    private function getDeletableWithTrashed(DeletionRequest $deletionRequest)
    {
        return match ($deletionRequest->deletable_type) {
            User::class => User::withTrashed()->find($deletionRequest->deletable_id),
            Admin::class => Admin::withTrashed()->find($deletionRequest->deletable_id),
            default => null
        };
    }
} 