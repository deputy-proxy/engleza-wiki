<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read TestAttempt $attempt
 * @property-read Question $question
 * @property-read QuestionOption|null $option
 */
class TestAnswer extends Model
{
    protected $fillable = ['test_attempt_id', 'question_id', 'question_option_id', 'answer_text', 'answer_payload', 'is_correct', 'points_awarded', 'answered_at'];

    protected $casts = ['answer_payload' => 'array', 'answered_at' => 'datetime', 'is_correct' => 'bool'];

    /** @return BelongsTo<TestAttempt, $this> */
    public function attempt()
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /** @return BelongsTo<QuestionOption, $this> */
    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
