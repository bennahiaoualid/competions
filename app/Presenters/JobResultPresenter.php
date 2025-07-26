<?php

namespace App\Presenters;

use Illuminate\Support\Str;

/**
 * Transforms raw job result into user-friendly format.
 *
 * ⚠️ IMPORTANT:
 * If you add or change keys in the job result array,
 * make sure to update the translation file:
 * resources/lang/{locale}/job.php under 'result_keys'.
 */

class JobResultPresenter
{
    protected array $result;

    public function __construct(array $result)
    {
        $this->result = $result;
    }

    public function toDisplay(): array
    {
        return collect($this->result)
            ->mapWithKeys(function ($value, $key) {
                return [$this->translateKey($key) => $this->formatValue($key, $value)];
            })
            ->toArray();
    }

    protected function translateKey(string $key): string
    {
        // Translation keys like: job.result_keys.auditor_id
        return __('job.result_keys.' . $key);
    }

    protected function formatValue(string $key, mixed $value): mixed
    {
        if (Str::endsWith($key, '_at')) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i');
        }

        return $value;
    }
}

