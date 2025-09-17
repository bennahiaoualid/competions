<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

Trait RegisterLogs
{
    /**
     * log the giving exception detail in log file with special name.
     *
     * @param string $title The incoming request containing admin data.
     * @param Exception $exception The incoming request containing admin data.
     */
    function registerLogs(string $title,Throwable $exception): void
    {

        Log::error($title . $exception->getMessage(), [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
