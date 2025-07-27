<?php

namespace App\Services\Notification;

use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\User;
use App\Notifications\User\CompetitionNotification;
use Illuminate\Support\Collection;

class CompetitionNotificationService
{
    /**
     * Send notification to all users in a competition
     */
    public function notifyCompetitionUsers(Competition $competition, string $eventType, Level $level = null, array $additionalData = []): void
    {
        $users = $competition->users;
        
        if ($users->isEmpty()) {
            return;
        }

        $notification = new CompetitionNotification($eventType, $competition, $level, $additionalData);
        
        // Send to all users in the competition
        $users->each(function (User $user) use ($notification) {
            $user->notify($notification);
        });
    }

    /**
     * Send notification to specific users
     */
    public function notifyUsers(Collection $users, Competition $competition, string $eventType, Level $level = null, array $additionalData = []): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $notification = new CompetitionNotification($eventType, $competition, $level, $additionalData);
        
        $users->each(function (User $user) use ($notification) {
            $user->notify($notification);
        });
    }

    /**
     * Send notification to a single user
     */
    public function notifyUser(User $user, Competition $competition, string $eventType, Level $level = null, array $additionalData = []): void
    {
        $notification = new CompetitionNotification($eventType, $competition, $level, $additionalData);
        $user->notify($notification);
    }

    /**
     * Competition created event
     */
    public function competitionCreated(Competition $competition): void
    {
        // Get users who are eligible for this competition (based on age, etc.)
        $eligibleUsers = $this->getEligibleUsersForCompetition($competition);
        $this->notifyUsers($eligibleUsers, $competition, 'created');
    }

    /**
     * Competition updated event
     */
    public function competitionUpdated(Competition $competition): void
    {
        $this->notifyCompetitionUsers($competition, 'updated');
    }

    /**
     * Competition activated event
     */
    public function competitionActivated(Competition $competition): void
    {
        $this->notifyCompetitionUsers($competition, 'activated');
    }

    /**
     * Level created event
     */
    public function levelCreated(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_created', $level);
    }

    /**
     * Level updated event
     */
    public function levelUpdated(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_updated', $level);
    }

    /**
     * Level activated event
     */
    public function levelActivated(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_activated', $level);
    }

    /**
     * Level finished event
     */
    public function levelFinished(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_finished', $level);
    }

    /**
     * Get users eligible for a competition based on age criteria
     */
    protected function getEligibleUsersForCompetition(Competition $competition): Collection
    {
        return User::where('age', '>=', $competition->age_start)
            ->where('age', '<=', $competition->age_end)
            ->get();
    }
} 