<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WritingSubmissionVersion extends Model
{
    protected $fillable = ['writing_submission_id', 'version_number', 'content', 'word_count', 'status', 'submitted_at'];

    protected $casts = ['submitted_at' => 'datetime'];

    /** @return BelongsTo<WritingSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(WritingSubmission::class, 'writing_submission_id');
    }

    /** @return HasMany<WritingSubmissionReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(WritingSubmissionReview::class, 'writing_submission_version_id');
    }
}
