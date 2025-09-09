<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait UserResponseCalculation
{
    /**
     * Calculate the penalty for a user's response
     * @param int $duration
     * @param int $keystrokes
     * @param string $answer
     * @return array [flags, penalty]
     */
    public function calculatePenalty(int $duration, ?int $keystrokes, ?string $answer): array
    {

        $flags = [];
        $penalty = 0;
    
        $length = strlen($answer);
        $wordCount = str_word_count(strip_tags($answer));

        if($length == 0){
            return [
                'flags' => json_encode($flags),
                'penalty' => 0
            ];
        }
    
        // Penalty: Answer too fast and long => likely copy/paste
        $cps = $length / max($duration, 0.01); // characters per second

        if ($cps > 10) {
            $flags[] = 'too_fast_long_answer';
            $penalty += 0.2; 
        }

        // Penalty: Very low keystroke-to-length ratio => likely pasted
        $keystrokeRatio = $keystrokes > 0 ? $length / $keystrokes : $length;
        if ($keystrokeRatio > 10) {
            $flags[] = 'low_keystrokes';
            $penalty += 0.2;
        }
    
        // Penalty: Words/minute exceed human threshold (~40-70 wpm)
        $wpm = $duration > 0 ? ($wordCount / $duration) * 60 : 0;
        if ($wpm > 90) {
            $flags[] = 'suspicious_wpm';
            $penalty += 0.1;
        }
    
        // Tab switch detected from session (set by JS)
        if (session('tab_switched')) {
            $flags[] = 'tab_switch';
            $penalty += 0.15;
            session()->forget('tab_switched');
        }

        return [
            'flags' => json_encode($flags),
            'penalty' => min(1.0, $penalty)
        ];
    }

    public function calculateUserResponseFinalScores($responses, $scores)
    {
        $response_counter = 0;
        $notifications = [];

        foreach ($responses as $response) {
            $response_counter++;

            $score = (float) $scores[$response->id];
            if($score !== $response->score){

                if ($score > $response->question->max_score) {
                    $notifications[] = [
                        __('messages.validation.not_allow.audit_score_greater_then_max', ['number' => $response_counter]),
                        "error"
                    ];
                    continue;
                }
    
                // claculate score
                if ($score === 0 || $response->response_duration >= $response->question->duration) {
                    $calc_score = $score / 2;
                } else {
                    $calc_score = $score - $response->response_duration / $response->question->duration * ($score / 2);
                }
                $penalty = $calc_score * $response->penalty;
                $response->score = $score;
                $response->final_score = round($calc_score - $penalty, 2);
                $response->ai_generated = false;
                $response->ai_score_generated_at = null;
            }

            
            $response->admin_id = Auth::id();
            $response->save();

            $notifications[] = [
                __('messages.validation.success.response_audited', ['number' => $response_counter]),
                "success"
            ];
        }

        return $notifications;
    }

    /**
     * Calculate final score for a single response without any database operations
     * Optimized for AI auditing - lightweight and fast
     * 
     * @param Response $response The response object
     * @param float $score The score to calculate from
     * @return float The calculated final score
     */
    protected function calculateSingleResponseFinalScore($response, $question, float $score): float
    {
        // Apply the same scoring logic as the full method but without DB operations
        if ($score === 0 || $response->response_duration >= $question->duration) {
            $calcScore = $score / 2;
        } else {
            $calcScore = $score - $response->response_duration / $question->duration * ($score / 2);
        }

        // Apply penalty if exists (no DB query needed)
        $penalty = $calcScore * ($response->penalty ?? 0);
        
        return round($calcScore - $penalty, 2);
    }
}
