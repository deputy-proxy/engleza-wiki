<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WritingSubmissionReview extends Model
{
    protected $fillable = [
        'writing_submission_id', 'writing_submission_version_id', 'teacher_id',
        'rubric_scores', 'overall_feedback', 'strengths', 'improvements', 'next_steps', 'published_at',
    ];

    protected $casts = ['rubric_scores' => 'array', 'published_at' => 'datetime'];

    /** @return BelongsTo<WritingSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(WritingSubmission::class, 'writing_submission_id');
    }

    /** @return BelongsTo<WritingSubmissionVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(WritingSubmissionVersion::class, 'writing_submission_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
