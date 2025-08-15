<?php

namespace App\Enums;

enum AISubjectEnum: string
{
    case RANDOM = 'random';
    case PHYSICS = 'physics';
    case MATHEMATICS = 'mathematics';
    case ENGLISH = 'english';
    case CHEMISTRY = 'chemistry';
    case BIOLOGY = 'biology';
    case HISTORY = 'history';
    case GEOGRAPHY = 'geography';
    case COMPUTER_SCIENCE = 'computer_science';

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the subject
     */
    public function label(): string
    {
        return __('competition.ai.subjects.' . $this->value);
    }

    /**
     * Get base cost for the subject
     */
    public function getBaseCost(): int
    {
        return match($this) {
            self::RANDOM => 10,
            self::PHYSICS => 10,
            self::MATHEMATICS => 10,
            self::ENGLISH => 10,
            self::CHEMISTRY => 10,
            self::BIOLOGY => 10,
            self::HISTORY => 10,
            self::GEOGRAPHY => 10,
            self::COMPUTER_SCIENCE => 10,
        };
    }

    public function getContext(AIDifficultyEnum $difficulty): string
    {
        return match($this) {
            self::MATHEMATICS => $this->getMathContext($difficulty),
            self::ENGLISH => $this->getEnglishContext($difficulty),
            self::HISTORY => $this->getHistoryContext($difficulty),
            self::GEOGRAPHY => $this->getGeographyContext($difficulty),
            self::BIOLOGY => $this->getBiologyContext($difficulty),
            self::CHEMISTRY => $this->getChemistryContext($difficulty),
            self::PHYSICS => $this->getPhysicsContext($difficulty),
            self::COMPUTER_SCIENCE => $this->getComputerScienceContext($difficulty),
            self::RANDOM => "Choose an appropriate subject and complexity level that matches the difficulty.",
        };
    }
    private function getMathContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic arithmetic, simple geometry, counting, or elementary math concepts.",
            AIDifficultyEnum::MEDIUM => "Cover algebra, geometry, fractions, percentages, or pre-calculus topics.",
            AIDifficultyEnum::HARD => "Include calculus, advanced algebra, trigonometry, statistics, or complex problem-solving.",
            AIDifficultyEnum::RANDOM => "Choose appropriate mathematical concepts for the selected difficulty level.",
        };
    }

    private function getEnglishContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic grammar, simple vocabulary, reading comprehension, or spelling.",
            AIDifficultyEnum::MEDIUM => "Cover sentence structure, literary devices, writing techniques, or intermediate vocabulary.",
            AIDifficultyEnum::HARD => "Include complex literary analysis, advanced rhetoric, critical writing, or linguistic concepts.",
            AIDifficultyEnum::RANDOM => "Choose appropriate English/Language Arts concepts for the selected difficulty level.",
        };
    }

    private function getComputerScienceContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic computer concepts, simple programming terms, or digital literacy.",
            AIDifficultyEnum::MEDIUM => "Cover programming fundamentals, data structures, algorithms, or software concepts.",
            AIDifficultyEnum::HARD => "Include advanced algorithms, system design, theoretical computer science, or complex programming concepts.",
            AIDifficultyEnum::RANDOM => "Choose appropriate computer science concepts for the selected difficulty level.",
        };
    }

    private function getScienceContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic scientific facts, simple observations, or elementary science concepts.",
            AIDifficultyEnum::MEDIUM => "Cover scientific principles, cause-and-effect relationships, or intermediate theories.",
            AIDifficultyEnum::HARD => "Include complex theories, advanced scientific principles, or research-level concepts.",
            AIDifficultyEnum::RANDOM => "Choose appropriate scientific concepts for the selected difficulty level.",
        };
    }

    private function getHistoryContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on major historical events, famous figures, or basic chronology.",
            AIDifficultyEnum::MEDIUM => "Cover historical causes and effects, cultural movements, or comparative history.",
            AIDifficultyEnum::HARD => "Include complex historical analysis, multiple perspectives, or historiographical concepts.",
            AIDifficultyEnum::RANDOM => "Choose appropriate historical concepts for the selected difficulty level.",
        };
    }

    private function getLiteratureContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic story elements, character identification, or simple themes.",
            AIDifficultyEnum::MEDIUM => "Cover literary devices, theme analysis, or author techniques.",
            AIDifficultyEnum::HARD => "Include complex literary criticism, symbolic interpretation, or comparative literature.",
            AIDifficultyEnum::RANDOM => "Choose appropriate literary concepts for the selected difficulty level.",
        };
    }

    private function getBiologyContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic life processes, animal identification, or simple body systems.",
            AIDifficultyEnum::MEDIUM => "Cover cell biology, genetics basics, or ecosystem relationships.",
            AIDifficultyEnum::HARD => "Include molecular biology, advanced genetics, or complex biological processes.",
            AIDifficultyEnum::RANDOM => "Choose appropriate biological concepts for the selected difficulty level.",
        };
    }

    private function getChemistryContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic elements, simple compounds, or states of matter.",
            AIDifficultyEnum::MEDIUM => "Cover chemical reactions, periodic table, or molecular structure.",
            AIDifficultyEnum::HARD => "Include organic chemistry, thermodynamics, or advanced chemical principles.",
            AIDifficultyEnum::RANDOM => "Choose appropriate chemical concepts for the selected difficulty level.",
        };
    }

    private function getPhysicsContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on basic forces, simple motion, or everyday physics phenomena.",
            AIDifficultyEnum::MEDIUM => "Cover mechanics, energy, waves, or electromagnetic concepts.",
            AIDifficultyEnum::HARD => "Include quantum mechanics, relativity, or advanced theoretical physics.",
            AIDifficultyEnum::RANDOM => "Choose appropriate physics concepts for the selected difficulty level.",
        };
    }

    private function getGeographyContext(AIDifficultyEnum $difficulty): string
    {
        return match($difficulty) {
            AIDifficultyEnum::EASY => "Focus on country/capital identification, basic landforms, or major rivers/mountains.",
            AIDifficultyEnum::MEDIUM => "Cover climate patterns, population distribution, or economic geography.",
            AIDifficultyEnum::HARD => "Include geopolitical analysis, complex geographical processes, or spatial relationships.",
            AIDifficultyEnum::RANDOM => "Choose appropriate geographical concepts for the selected difficulty level.",
        };
    }
} 