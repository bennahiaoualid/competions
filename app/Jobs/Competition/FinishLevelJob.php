<?php

namespace App\Jobs\Competition;


use Throwable;
use Illuminate\Bus\Queueable;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use App\Http\Helpers\UserNotifyEmail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\LevelRepositoryInterface;

class FinishLevelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Level $level;

    public function __construct(Level $level)
    {
        $this->level = $level->fresh('competition.users', 'competition.auditors');
    }

    public function handle(
        TransactionManagerInterface $transactionManager,
        LevelRepositoryInterface $levelRepository,
        FlasherInterface $flasher,
    ): void {

        $result = $transactionManager->run(function () use ($levelRepository) {
            $levelRepository->insertMissingResponsesForLevel($this->level);
            $this->assignUsersToAuditors($levelRepository);

            $updated = $levelRepository->update($this->level, ['status' => 2]);

            if ($updated) {
                UserNotifyEmail::auditorsFinishLevel($this->level->competition, $this->level);
            }

            return $updated;
        });

        if ($result) {
            $flasher->notifyCrudResult(true, 'finish');
        } else {
            $flasher->notifyCrudResult(false, 'finish');
        }
    }

    public function failed(Throwable $exception): void
    {
        app(FlasherInterface::class)->notifyCrudResult(false, 'finish');
        logger()->error('FinishLevelJob failed: ' . $exception->getMessage(), [
            'level_id' => $this->level->id,
            'exception' => $exception,
        ]);
    }

    protected function assignUsersToAuditors(LevelRepositoryInterface $levelRepository): void
    {
        $users = $this->level->competition->users;
        $auditors = $this->level->competition->auditors;

        $levelRepository->assignAuditorsToUsersInPivot($this->level, $users, $auditors);
    }

    public function getLevel(): Level
    {
        return $this->level;
    }
}