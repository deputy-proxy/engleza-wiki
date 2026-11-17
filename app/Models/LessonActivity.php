<?php

namespace App\Models;

use App\Models\Resource as LearningResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class LessonActivity extends Model
{
    protected $fillable = ['learning_lesson_id', 'type', 'resource_id', 'test_id', 'title', 'instructions', 'sort_order', 'is_required'];

    protected $casts = ['is_required' => 'bool'];

    protected static function booted(): void
    {
        static::saving(function (LessonActivity $activity): void {
            $validResource = $activity->type === 'resource' && $activity->resource_id !== null && $activity->test_id === null;
            $validTest = $activity->type === 'test' && $activity->test_id !== null && $activity->resource_id === null;

            if (! $validResource && ! $validTest) {
                throw ValidationException::withMessages(['type' => 'Choose exactly one matching resource or test for this activity.']);
            }

            if ($activity->lesson()->where('status', 'published')->exists()) {
                throw ValidationException::withMessages(['learning_lesson_id' => 'Unpublish the lesson before changing its activities, then republish it to re-run publication checks.']);
            }
        });

        static::deleting(function (LessonActivity $activity): void {
            if ($activity->lesson()->where('status', 'published')->exists()) {
                throw ValidationException::withMessages(['learning_lesson_id' => 'Unpublish the lesson before removing one of its activities.']);
            }
        });
    }

    /** @return BelongsTo<LearningLesson, $this> */
    public function lesson()
    {
        return $this->belongsTo(LearningLesson::class, 'learning_lesson_id');
    }

    /** @return BelongsTo<LearningResource, $this> */
    public function resource()
    {
        return $this->belongsTo(LearningResource::class);
    }

    /** @return BelongsTo<Test, $this> */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /** @return HasMany<LessonActivityProgress, $this> */
    public function progress()
    {
        return $this->hasMany(LessonActivityProgress::class);
    }
}
