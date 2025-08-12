<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_trans_key'
    ];

    /**
     * Get the setting value as integer
     */
    public function getValueAsInt(): int
    {
        return (int) $this->setting_value;
    }

    /**
     * Get the setting value as float
     */
    public function getValueAsFloat(): float
    {
        return (float) $this->setting_value;
    }

    /**
     * Get the setting value as boolean
     */
    public function getValueAsBool(): bool
    {
        return filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the setting value as array (JSON)
     */
    public function getValueAsArray(): array
    {
        return json_decode($this->setting_value, true) ?: [];
    }
} 