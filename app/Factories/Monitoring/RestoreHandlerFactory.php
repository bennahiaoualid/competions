<?php
namespace App\Factories\Monitoring;

use App\Models\User;
use App\Models\Admin\Admin;
use InvalidArgumentException;
use App\Contracts\FlasherInterface;
use App\Models\Monitoring\DeletionRequest;
use App\Contracts\TransactionManagerInterface;
use App\Services\Monitoring\JobTrackingService;
use App\Services\Monitoring\DeletionRequest\UserRestoreHandler;
use App\Services\Monitoring\DeletionRequest\AdminRestoreHandler;
use App\Interface\Monitoring\DeletionRequests\RestorableHandlerInterface;

/**
 * Factory for creating restore handlers based on entity type.
 * 
 * This factory is responsible for creating the appropriate restore handler
 * for different entity types (User, Admin, etc.). It follows the Factory pattern
 * to provide a clean interface for handler creation and supports the Open/Closed
 * principle by allowing easy extension for new entity types.
 * 
 * @package App\Factories\Monitoring
 */
class RestoreHandlerFactory
{
    /**
     * Create a new restore handler factory instance.
     *
     * @param TransactionManagerInterface $tx Service for managing database transactions
     * @param JobTrackingService $jobTrackingService Service for tracking job execution
     * @param FlasherInterface|null $flasher Service for displaying flash messages to users
     */
    public function __construct(
        private TransactionManagerInterface $tx,
        private JobTrackingService $jobTrackingService,
        private ?FlasherInterface $flasher = null
    ) {}

    /**
     * Create a restore handler for the specified entity type.
     *
     * This method creates the appropriate restore handler based on the
     * entity type of the deletable object. It supports User and Admin entities
     * and can be easily extended for additional entity types.
     *
     * @param mixed $deletable The entity to be restored
     * @param DeletionRequest $request The deletion request record
     * @param Admin $initiator The admin initiating the restoration
     * @return RestorableHandlerInterface The appropriate restore handler
     * @throws \InvalidArgumentException If the entity type is not supported
     */
    public function make($deletable, DeletionRequest $request, Admin $initiator): RestorableHandlerInterface
    {
        return match (get_class($deletable)) {
            User::class => new UserRestoreHandler($deletable, $request, $this->tx, $initiator, $this->flasher),
            Admin::class => new AdminRestoreHandler($deletable, $request, $initiator, $this->jobTrackingService, $this->flasher),
            default => throw new \InvalidArgumentException("Unsupported deletable type"),
        };
    }
}
