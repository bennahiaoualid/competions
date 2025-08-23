<?php

namespace App\Enums;

enum AIDifficultyEnum: string
{
    case RANDOM = 'random';
    case EASY = 'easy';
    case MEDIUM = 'medium';
    case HARD = 'hard';

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the difficulty
     */
    public function label(): string
    {
        return __('competition.ai.difficulties.' . $this->value);
    }

    /**
     * Get multiplier for the difficulty
     */
    public function getMultiplier(): float
    {
        return match($this) {
            self::RANDOM => 1.0,
            self::EASY => 1.0,
            self::MEDIUM => 1.0,
            self::HARD => 1.0,
        };
    }

    public function getGuidelines(): string
    {
        return match($this) {
            self::EASY => "Use simple, direct language, Focus on basic concepts and definitions, Avoid complex calculations or multi-step reasoning, Target elementary to middle school comprehension",
            self::MEDIUM => "Use standard academic vocabulary, Require application of concepts, not just recall, May include moderate calculations or logical reasoning, Target high school to early college level",
            self::HARD => "Use precise technical terminology, Require synthesis of multiple concepts, May involve complex problem-solving or critical analysis, Target college to professional level",
            self::RANDOM => "Choose appropriate complexity for the subject matter.",
        };
    }
} 