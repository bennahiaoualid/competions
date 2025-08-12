<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    /**
     * Cache key for system settings
     */
    private const CACHE_KEY = 'system_settings';
    
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * get all settings (static for singleton performance)
     */
    private static $allSettings = null;

    public function __construct()
    {
    }

    /**
     * Get a system setting value
     */
    public function getValue(string $key, $default = null)
    {
        $setting = $this->getSetting($key);
        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Get a system setting value as integer
     */
    public function getValueAsInt(string $key, int $default = 0): int
    {
        $value = $this->getValue($key, $default);
        return (int) $value;
    }

    /**
     * Get a system setting value as float
     */
    public function getValueAsFloat(string $key, float $default = 0.0): float
    {
        $value = $this->getValue($key, $default);
        return (float) $value;
    }

    /**
     * Get a system setting value as boolean
     */
    public function getValueAsBool(string $key, bool $default = false): bool
    {
        $value = $this->getValue($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get a system setting value as array (JSON)
     */
    public function getValueAsArray(string $key, array $default = []): array
    {
        $value = $this->getValue($key, $default);
        return json_decode($value, true) ?: $default;
    }

    /**
     * Set a system setting value
     */
    public function setValue(string $key, string $value): bool
    {
        $setting = SystemSetting::where('setting_key', $key)->first();
        if (!$setting) {
            return false;
        }

        // Validate the new value
        if (!$this->validateValue($key, $value)) {
            return false;
        }

        $updated = $setting->update(['setting_value' => $value]);
        
        if ($updated) {
            $this->clearCache();
        }

        return $updated;
    }

    /**
     * Get a system setting model
     */
    public function getSetting(string $key): ?SystemSetting
    {
        $settings = self::$allSettings ?? $this->getAllSettings();
        return $settings->firstWhere('setting_key', $key);
    }

    /**
     * Get all system settings
     */
    public function getAllSettingsFromCache(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::all();
        });
    }

    /**
     * Check if settings are already loaded in memory
     */
    public function isSettingsLoaded(): bool
    {
        return self::$allSettings !== null;
    }

    public function getAllSettings(): Collection
    {
        if (self::$allSettings === null) {
            self::$allSettings = $this->getAllSettingsFromCache();
        }
        return self::$allSettings;
    }

    /**
     * Get settings by category (e.g., 'payment', 'system', 'competition')
     */
    public function getSettingsByCategory(string $category): Collection
    {
        return $this->allSettings->filter(function ($setting) use ($category) {
            return str_starts_with($setting->setting_trans_key, "settings.{$category}.");
        });
    }

    /**
     * Validate a setting value against configuration constraints
     */
    public function validateValue(string $key, $value): bool
    {
        $validationRules = config("settings.validation.{$key}");
        
        if (!$validationRules) {
            return true; // No validation rules defined
        }

        $type = $validationRules['type'] ?? 'string';
        $min = $validationRules['min'] ?? null;
        $max = $validationRules['max'] ?? null;

        // Type validation
        switch ($type) {
            case 'integer':
                if (!is_numeric($value) || (int) $value != $value) {
                    return false;
                }
                $value = (int) $value;
                break;
                
            case 'decimal':
                if (!is_numeric($value)) {
                    return false;
                }
                $value = (float) $value;
                break;
                
            case 'boolean':
                if (!in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'])) {
                    return false;
                }
                break;
        }

        // Range validation
        if ($min !== null && $value < $min) {
            return false;
        }

        if ($max !== null && $value > $max) {
            return false;
        }

        return true;
    }

    /**
     * Get validation error message for a setting
     */
    public function getValidationMessage(string $key): string
    {
        $validationRules = config("settings.validation.{$key}");
        return $validationRules['message'] ? __($validationRules['message']) : 'Invalid value for setting: ' . $key;
    }

    /**
     * Get validation constraints for a setting
     */
    public function getValidationConstraints(string $key): array
    {
        $validationRules = config("settings.validation.{$key}");
        return $validationRules ?: [];
    }

    /**
     * Clear the system settings cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$allSettings = null;
    }

    /**
     * Refresh the system settings cache
     */
    public function refreshCache(): void
    {
        $this->clearCache();
        $this->allSettings = $this->getAllSettingsFromCache(); // This will rebuild the cache
    }

    /**
     * Check if maintenance mode is enabled
     */
    public function isMaintenanceMode(): bool
    {
        return $this->getValueAsBool('maintenance_mode', false);
    }

    /**
     * Get payment-related settings
     */
    public function getPaymentSettings(): Collection
    {
        return $this->getSettingsByCategory('payment');
    }

    /**
     * Get system-related settings
     */
    public function getSystemSettings(): Collection
    {
        return $this->getSettingsByCategory('system');
    }

    /**
     * Get competition-related settings
     */
    public function getCompetitionSettings(): Collection
    {
        return $this->getSettingsByCategory('competition');
    }

    /**
     * Get notification-related settings
     */
    public function getNotificationSettings(): Collection
    {
        return $this->getSettingsByCategory('notifications');
    }

    /**
     * Filter settings by category from existing collection (no cache hit)
     */
    public function filterSettingsByCategory(Collection $settings, string $category): Collection
    {
        return $settings->filter(function ($setting) use ($category) {
            return str_starts_with($setting->setting_trans_key, "settings.{$category}.");
        });
    }
} 