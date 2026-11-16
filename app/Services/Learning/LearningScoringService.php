<?php

namespace App\Services\Learning;

use App\Models\LearningAnswer;
use App\Models\LearningAttempt;

class LearningScoringService
{
    public function score(LearningAttempt $attempt): LearningAttempt
    {
        $answers = LearningAnswer::with('question', 'option')->where('learning_attempt_id', $attempt->id)->get();
        $total = 0;
        $earned = 0;
        foreach ($answers as $a) {
            $pts = $a->question->points ?? 1;
            $total += $pts;
            $ok = $a->option && $a->option->is_correct;
            $a->is_correct = (bool) $ok;
            $a->points_awarded = $ok ? $pts : 0;
            $a->save();
            if ($ok) {
                $earned += $pts;
            }
        }
        $attempt->score = $earned;
        $attempt->percentage = $total > 0 ? round(($earned / $total) * 100, 1) : 0;
        $attempt->status = 'completed';
        $attempt->completed_at = now();
        $attempt->save();

        return $attempt;
    }
}
