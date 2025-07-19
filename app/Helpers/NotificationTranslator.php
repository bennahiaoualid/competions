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

    /**
     * Transform a notification model or array to a key-value array with translation and fallback.
     *
     * @param \Illuminate\Notifications\DatabaseNotification|array $notification
     * @return array
     */
    public static function transformNotification($notification): array
    {
        $data = is_array($notification) ? ($notification['data'] ?? []) : $notification->data;
        $id = is_array($notification) ? ($notification['id'] ?? null) : $notification->id;
        $readAt = is_array($notification) ? ($notification['read_at'] ?? null) : $notification->read_at;
        $createdAt = is_array($notification) ? ($notification['created_at'] ?? null) : $notification->created_at;

        // If notification has translation key, translate it server-side
        if (isset($data['translation_key'])) {
            $translationKey = $data['translation_key'];
            $translationData = $data['translation_data'] ?? [];

            $titleKey = $translationKey . '.title';
            $messageKey = $translationKey . '.message';

            $translatedTitle = __( $titleKey, $translationData );
            $translatedMessage = __( $messageKey, $translationData );

            $linkText = null;
            if (!empty($data['link'])) {
                $linkText = __('notifications.link_text.detail');
            }

            return [
                'id' => $id,
                'title' => $translatedTitle,
                'message' => $translatedMessage,
                'notification_priority_type' => $data['notification_priority_type'] ?? 'info',
                'link' => $data['link'] ?? null,
                'link_text' => $linkText,
                'read_at' => $readAt,
                'created_at' => $createdAt,
            ];
        }

        // Fallback for notifications without translation keys
        $linkText = null;
        if (!empty($data['link'])) {
            $linkText = __('notifications.link_text.detail');
        }

        return [
            'id' => $id,
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'notification_priority_type' => $data['notification_priority_type'] ?? 'info',
            'link' => $data['link'] ?? null,
            'link_text' => $linkText,
            'read_at' => $readAt,
            'created_at' => $createdAt,
        ];
    }

    /**
     * Get a field value from notification data, using translation if available, otherwise pure value, or empty if not present.
     *
     * @param \Illuminate\Notifications\DatabaseNotification|array $notification
     * @param string $fieldName (e.g., 'title' or 'message')
     * @return string
     */
    public static function getFieldOrTranslation($notification, string $fieldName): string
    {
        $data = is_array($notification) ? ($notification['data'] ?? []) : $notification->data;
        // If translation key exists, try to translate
        if (isset($data['translation_key'])) {
            $translationKey = $data['translation_key'] . '.' . $fieldName;
            $translationData = $data['translation_data'] ?? [];
            $translated = __($translationKey, $translationData);
            // If translation exists and is not the key itself, return it
            if ($translated !== $translationKey) {
                return $translated;
            }
        }
        // Fallback: return pure value if exists
        if (isset($data[$fieldName])) {
            return $data[$fieldName];
        }
        // Otherwise, return empty string
        return '';
    }
} 