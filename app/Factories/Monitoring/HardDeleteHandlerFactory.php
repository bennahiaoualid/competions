<?php

namespace App\Factories\Monitoring;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use InvalidArgumentException;
use App\Contracts\FlasherInterface;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\JobTrackingService;
use App\Services\Monitoring\DeletionRequest\UserHardDeleteHandler;
use App\Services\Monitoring\DeletionRequest\AdminHardDeleteHandler;
use App\Interface\Monitoring\DeletionRequests\HardDeleteHandlerInterface;

/**
 * Factory for creating hard delete handlers based on entity type.
 * 
 * This factory is responsible for creating the appropriate hard delete handler
 * for different entity types (User, Admin, etc.). It follows the Factory pattern
 * to provide a clean interface for handler creation and supports the Open/Closed
 * principle by allowing easy extension for new entity types.
 * 
 * @package App\Factories\Monitoring
 */
class HardDeleteHandlerFactory
{
    /**
     * Create a new hard delete handler factory instance.
     *
     * @param JobTrackingService $jobService Service for tracking job execution
     * @param FlasherInterface $flasher Service for displaying flash messages to users
     */
    public function __construct(
        private JobTrackingService $jobService,
        private FlasherInterface $flasher
    ) {}

    /**
     * Create a hard delete handler for the specified entity type.
     *
     * This method creates the appropriate hard delete handler based on the
     * entity type of the deletable object. It supports User and Admin entities
     * and can be easily extended for additional entity types.
     *
     * @param mixed $deletable The entity to be hard deleted
     * @param DeletionRequest $deletionRequest The deletion request record
     * @param Admin $admin_auth The authenticated admin performing the deletion
     * @param Request|null $request The HTTP request containing additional parameters
     * @return HardDeleteHandlerInterface The appropriate hard delete handler
     * @throws \InvalidArgumentException If the entity type is not supported
     */
    public function make($deletable, DeletionRequest $deletionRequest, Admin $admin_auth, ?Request $request = null): HardDeleteHandlerInterface
    {
        return match (get_class($deletable)) {
            User::class => new UserHardDeleteHandler($deletable, $deletionRequest, $admin_auth, $this->jobService, $this->flasher),
            Admin::class => new AdminHardDeleteHandler($deletable, $deletionRequest, $admin_auth, $this->jobService, $this->flasher, $request),
            default => throw new \InvalidArgumentException("Unsupported deletable type: " . get_class($deletable)),
        };
    }
}
