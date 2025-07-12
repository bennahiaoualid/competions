<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class NotificationTranslator
{
    /**
     * Translate a notification message with the current locale.
     *
     * @param string $key The translation key
     * @param array $data The data to interpolate
     * @param string|null $locale The locale to use (defaults to current)
     * @return string
     */
    public static function translate(string $key, array $data = [], ?string $locale = null): string
    {
        $currentLocale = App::getLocale();
        
        if ($locale) {
            App::setLocale($locale);
        }
        
        $translation = __($key, $data);
        
        if ($locale) {
            App::setLocale($currentLocale);
        }
        
        return $translation;
    }

    /**
     * Get all translations for a notification key.
     *
     * @param string $key The translation key
     * @param array $data The data to interpolate
     * @param array $locales The locales to translate to
     * @return array
     */
    public static function translateToAllLocales(string $key, array $data = [], array $locales = ['en', 'ar']): array
    {
        $translations = [];
        $currentLocale = App::getLocale();
        
        foreach ($locales as $locale) {
            App::setLocale($locale);
            $translations[$locale] = __($key, $data);
        }
        
        App::setLocale($currentLocale);
        
        return $translations;
    }

    /**
     * Get notification data with translations for all supported locales.
     *
     * @param string $translationKey The base translation key
     * @param array $translationData The data for interpolation
     * @param array $additionalData Additional notification data
     * @return array
     */
    public static function getNotificationData(string $translationKey, array $translationData = [], array $additionalData = []): array
    {
        $baseData = [
            'translation_key' => $translationKey,
            'translation_data' => $translationData,
            'translations' => self::translateToAllLocales($translationKey, $translationData),
        ];
        
        return array_merge($baseData, $additionalData);
    }
} 