<?php

namespace App\Events\Notifications\Admin;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditorDeletionFailed
{
    use Dispatchable, SerializesModels;

    public $competition;
    public $auditor;
    public $owner;
    public $deadline;

    public function __construct($competition, $auditor, $owner, $deadline)
    {
        $this->competition = $competition;
        $this->auditor = $auditor;
        $this->owner = $owner;
        $this->deadline = $deadline;
    }
} 