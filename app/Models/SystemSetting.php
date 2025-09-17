<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $setting_key
 * @property string $setting_value
 * @property string $setting_trans_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereSettingKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereSettingTransKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereSettingValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemSetting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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