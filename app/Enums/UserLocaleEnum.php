<?php

namespace App\Enums;

enum UserLocaleEnum: string
{
    case ENGLISH = 'en';
    case ARABIC = 'ar';
    case SPANISH = 'es';
    case FRENCH = 'fr';

    /**
     * Get all locale values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get locale label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ENGLISH => 'English',
            self::ARABIC => 'العربية',
            self::SPANISH => 'Español',
            self::FRENCH => 'Français',
        };
    }

    /**
     * Get text direction for the locale
     */
    public function getDirection(): string
    {
        return match($this) {
            self::ARABIC => 'rtl', // Right-to-left for Arabic
            self::ENGLISH, self::SPANISH, self::FRENCH => 'ltr', // Left-to-right for others
        };
    }

    /**
     * Get AI prompt description for the locale
     */
    public function getAiPromptDescription(): string
    {
        return match($this) {
            self::ENGLISH => 'Please respond in English language only. Use clear, professional English with proper grammar and spelling.',
            self::ARABIC => 'Please respond in Arabic language only. Use clear, professional Arabic with proper grammar and spelling.',
            self::SPANISH => 'Please respond in Spanish language only. Use clear, professional Spanish with proper grammar and spelling.',
            self::FRENCH => 'Please respond in French language only. Use clear, professional French with proper grammar and spelling.',
        };
    }

    /**
     * Get AI prompt description based on subject
     */
    public static function getAiPromptDescriptionForSubject(string $subject, ?UserLocaleEnum $userLocale = null): string
    {
        $userLocale = $userLocale ?? self::getDefault();
        
        // Check if subject is a language subject
        $subjectLanguage = match(strtolower($subject)) {
            'english', 'en' => self::ENGLISH,
            'arabic', 'ar' => self::ARABIC,
            'spanish', 'es' => self::SPANISH,
            'french', 'fr' => self::FRENCH,
            default => $userLocale // Use user locale for non-language subjects
        };
        
        return $subjectLanguage->getAiPromptDescription();
    }

    /**
     * Check if locale is RTL (right-to-left)
     */
    public function isRtl(): bool
    {
        return $this->getDirection() === 'rtl';
    }

    /**
     * Check if locale is LTR (left-to-right)
     */
    public function isLtr(): bool
    {
        return $this->getDirection() === 'ltr';
    }

    /**
     * Get locale from string value
     */
    public static function fromString(string $value): ?self
    {
        return match($value) {
            'en' => self::ENGLISH,
            'ar' => self::ARABIC,
            'es' => self::SPANISH,
            'fr' => self::FRENCH,
            default => null,
        };
    }

    /**
     * Get default locale
     */
    public static function getDefault(): self
    {
        // Try to get current app locale first
        $currentLocale = app()->getLocale();
        $locale = self::fromString($currentLocale);
        
        // If current locale is supported, use it; otherwise fall back to English
        return $locale ?? self::ENGLISH;
    }

    /**
     * Check if locale is supported
     */
    public static function isSupported(string $value): bool
    {
        return in_array($value, self::values());
    }
} 