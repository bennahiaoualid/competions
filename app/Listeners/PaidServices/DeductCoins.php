<?php

namespace App\Listeners\PaidServices;

use App\Models\Admin\Admin;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Payment\CoinPricingService;
use App\Events\PaidServices\AIQuestionGenerated;
use App\Services\Payment\CoinTransactionService;
use App\Events\PaidServices\AiBatchedAuditingsuccess;

class DeductCoins implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected CoinPricingService $coinPricingService,
        protected CoinTransactionService $coinTransactionService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AiBatchedAuditingsuccess $event): void
    {
        $user = Admin::find($event->userId);
        
        if ($user && $user->coinBalance) {
            $cost = round($event->cost,2);
            $user->coinBalance->decrement('balance', $cost);
            
            // Create spend transaction record
            $this->coinTransactionService->createAiAudtingResponsesTransaction($user, $cost);
            
            // TODO: Create user notification
        }
    }
} 