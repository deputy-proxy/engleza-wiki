<?php

namespace App\Services\Learning;

use App\Models\LearningAttempt;

class LearningScoringService
{
    public function __construct(private readonly LearningAttemptService $attempts) {}

    public function score(LearningAttempt $attempt): LearningAttempt
    {
        return $this->attempts->complete($attempt, $attempt->user_id, $attempt->session_id);
    }
}
