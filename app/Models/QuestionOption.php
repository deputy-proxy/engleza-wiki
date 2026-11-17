<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Question $question
 */
class QuestionOption extends Model
{
    protected $fillable = ['question_id', 'label', 'value', 'is_correct', 'sort_order'];

    protected $casts = ['is_correct' => 'bool'];

    /** @return BelongsTo<Question, $this> */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
