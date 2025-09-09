<?php

namespace App\Observers;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminAvailability;

class AdminObserver
{
    /**
     * Handle the Admin "created" event.
     */
    public function created(Admin $admin): void
    {
        AdminAvailability::create(['admin_id' => $admin->id]);
        $admin->coinBalance()->firstOrCreate([
            'balanceable_id' => $admin->id,
        'balanceable_type' => Admin::class
        ]);
    }

    /**
     * Handle the Admin "updated" event.
     */
    public function updated(Admin $admin): void
    {
        //
    }

    /**
     * Handle the Admin "deleted" event.
     */
    public function deleted(Admin $admin): void
    {
        //
    }

    /**
     * Handle the Admin "restored" event.
     */
    public function restored(Admin $admin): void
    {
        //
    }

    /**
     * Handle the Admin "force deleted" event.
     */
    public function forceDeleted(Admin $admin): void
    {
        //
    }
}
