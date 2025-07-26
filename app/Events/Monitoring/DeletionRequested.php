<?php

namespace App\Events\Monitoring;


use App\Models\Admin\Admin;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;


class DeletionRequested
{
    use Dispatchable, SerializesModels;

    public object $deletable;
    public \App\Models\Admin\Admin $requestedBy;
    public string $reason;
    public string $name;

    public function __construct(object $deletable, Admin $requestedBy, string $reason, string $name)
    {
        $this->deletable = $deletable;
        $this->requestedBy = $requestedBy;
        $this->reason = $reason;
        $this->name = $name;
    }
}
