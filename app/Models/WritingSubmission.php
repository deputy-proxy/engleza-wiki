<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read WritingResource $writingResource
 * @property-read User $user
 * @property-read ClassGroup|null $classGroup
 * @property-read Collection<int, WritingSubmissionVersion> $versions
 * @property-read Collection<int, WritingSubmissionReview> $reviews
 */
class WritingSubmission extends Model
{
    public const STATUSES = [
        'draft', 'submitted', 'under_review', 'feedback_published',
        'revision_requested', 'resubmitted', 'completed',
    ];

    protected $fillable = ['writing_resource_id', 'user_id', 'class_group_id', 'content', 'word_count', 'submitted_at', 'status'];

    protected $casts = ['submitted_at' => 'datetime'];

    /** @return BelongsTo<WritingResource, $this> */
    public function writingResource(): BelongsTo
    {
        return $this->belongsTo(WritingResource::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ClassGroup, $this> */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** @return HasMany<WritingSubmissionVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(WritingSubmissionVersion::class)->orderBy('version_number');
    }

    /** @return HasMany<WritingSubmissionReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(WritingSubmissionReview::class)->latest('published_at');
    }
}
