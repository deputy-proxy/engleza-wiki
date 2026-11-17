<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdaptivePracticeAnswer extends Model
{
    protected $fillable = [
        'adaptive_practice_session_id', 'question_id', 'answer_payload',
        'is_correct', 'points_awarded', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answer_payload' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AdaptivePracticeSession, $this> */
    public function session()
    {
        return $this->belongsTo(AdaptivePracticeSession::class, 'adaptive_practice_session_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
