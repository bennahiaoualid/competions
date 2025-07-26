<?php

namespace App\Services;

use Throwable;
use App\Exceptions\OnlyAuditorException;
use App\Exceptions\OwnsActiveCompetitionWithRunningLevelException;

class ExceptionMessageTranslator
{
    public function translate(Throwable $e, $locale = null)
    {
        return match (true) {
            $e instanceof OnlyAuditorException => __('errors.only_auditor'),
            $e instanceof OwnsActiveCompetitionWithRunningLevelException => __('errors.owns_active_competition_with_running_level'),
            default => null,
        };
    }
} 