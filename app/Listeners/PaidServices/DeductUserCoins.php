<?php

namespace App\Listeners\PaidServices;

use App\Events\PaidServices\AIQuestionGenerated;
use App\Models\Payment\CoinBalance;
use App\Services\Payment\CoinPricingService;
use App\Services\Payment\CoinTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DeductUserCoins implements ShouldQueue
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
    public function handle(AIQuestionGenerated $event): void
    {
        $user = \App\Models\User::find($event->userId);
        
        if ($user && $user->coinBalance) {
            $user->coinBalance->decrement('balance', $event->cost);
            
            // Create spend transaction record
            $this->coinTransactionService->createQuestionGenerateTransaction($user, $event->cost);
            
            // TODO: Create user notification
        }
    }
} 