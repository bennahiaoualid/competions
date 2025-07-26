<?php
namespace App\Helpers;

use Throwable;
use App\Exceptions\OnlyAuditorException;
use App\Exceptions\OwnsActiveCompetitionWithRunningLevelException;

class ErrorTranslationHelper
{
    public static function getErrorTranslation(Throwable $e): ?array
    {
        if ($e instanceof OnlyAuditorException) {
            return [
                'key' => 'errors.only_auditor',
                'data' => ['competitions' => $e->getMessage()]
            ];
        }
        if ($e instanceof OwnsActiveCompetitionWithRunningLevelException) {
            return [
                'key' => 'errors.owns_active_competition_with_running_level',
                'data' => ['competitions' => $e->getMessage()]
            ];
        }
        return null;
    }
}