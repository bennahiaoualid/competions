<?php

namespace App\Listeners\GuestUsers;

use App\Events\GuestUsers\ResponseStorageFailed;
use App\Models\GuestUsers\GlobalResponse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CleanupFailedResponse implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ResponseStorageFailed $event): void
    {
        try {
            // Clean up the void response that was created in getRandomQuestion
            $deletedCount = GlobalResponse::where([
                'question_id' => $event->questionId,
                'user_id' => $event->userId,
                'choice_id' => null
            ])->delete();

            // Log the cleanup action
            Log::info('Cleaned up failed response', [
                'question_id' => $event->questionId,
                'user_id' => $event->userId,
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            // Log cleanup failure
            Log::error('Failed to cleanup failed response', [
                'question_id' => $event->questionId,
                'user_id' => $event->userId,
                'cleanup_error' => $e->getMessage(),
            ]);
        }
    }
} 