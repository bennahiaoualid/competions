<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->coinBalance()->firstOrCreate([
            'balanceable_id' => $user->id,
            'balanceable_type' => User::class
        ]);
    }


    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        $user->coinBalance()->delete();
    }
}
