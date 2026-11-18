<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Services\Assessment\QuestionResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/** @property-read LearningUnit|null $unit */
class LearningLesson extends Model
{
    use HasTranslations;

    protected $fillable = ['learning_unit_id', 'level_id', 'title', 'slug', 'description', 'objectives', 'status', 'sort_order', 'published_at'];

    protected $casts = ['objectives' => 'array', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (LearningLesson $lesson): void {
            if ($lesson->status !== 'published') {
                return;
            }
            $issues = $lesson->publicationIssues();
            if ($issues !== []) {
                throw ValidationException::withMessages(['status' => implode(' ', $issues)]);
            }
        });
    }

    /** @return array<int, string> */
    public function publicationIssues(): array
    {
        $issues = [];
        $unit = $this->unit()->with('path')->first();
        if ($this->published_at === null) {
            $issues[] = 'A publication date is required.';
        }
        if ($unit === null || ! $unit->is_published || $unit->path === null) {
            $issues[] = 'The parent unit must be published and belong to a learning path.';
        }

        $activities = $this->activities()->with(['resource', 'test'])->get();
        if ($activities->isEmpty()) {
            $issues[] = 'Add at least one activity before publishing this lesson.';
        }

        foreach ($activities as $activity) {
            if ($activity->type === 'resource') {
                $resource = $activity->resource;
                if ($activity->resource_id === null || $activity->test_id !== null || $resource === null
                    || $resource->status !== 'published' || ($resource->published_at !== null && $resource->published_at->gt(now()))) {
                    $issues[] = 'Every resource activity must reference a currently published resource.';
                    break;
                }
                if (in_array($resource->type, ['listening', 'reading'], true)) {
                    $interactive = $resource->type === 'listening'
                        ? $resource->listeningResource()->with('questions.options')->first()
                        : $resource->readingResource()->with('questions.options')->first();
                    $questions = $interactive === null ? collect() : $interactive->questions;
                    if ($questions->isEmpty()) {
                        $issues[] = 'Interactive resources used in lessons must contain at least one question.';
                        break;
                    }
                    foreach ($questions as $question) {
                        if (app(QuestionResponseService::class)->definitionIssues($question) !== []) {
                            $issues[] = 'Every question in an interactive resource must have a valid supported exercise definition.';
                            break 2;
                        }
                    }
                }
            } elseif ($activity->type === 'test') {
                $test = $activity->test;
                if ($activity->test_id === null || $activity->resource_id !== null || $test === null
                    || $test->status !== 'published' || ! $test->is_public
                    || ! in_array($test->access_type, ['free', 'registered'], true)
                    || ($test->published_at !== null && $test->published_at->gt(now()))) {
                    $issues[] = 'Every test activity must reference a currently published, accessible test.';
                    break;
                }
                $questions = $test->sections()->with('questions.options')->get()->flatMap(fn ($section) => $section->questions);
                if ($questions->isEmpty()) {
                    $issues[] = 'Tests used in lessons must contain at least one question.';
                    break;
                }
                foreach ($questions as $question) {
                    if (app(QuestionResponseService::class)->definitionIssues($question) !== []) {
                        $issues[] = 'Every question in a lesson test must have a valid supported exercise definition.';
                        break 2;
                    }
                }
            } else {
                $issues[] = 'Every lesson activity must reference a resource or a test.';
                break;
            }
        }

        return $issues;
    }

    /** @return BelongsTo<LearningUnit, $this> */
    public function unit()
    {
        return $this->belongsTo(LearningUnit::class, 'learning_unit_id');
    }

    /** @return BelongsTo<Level, $this> */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    /** @return BelongsToMany<Topic, $this> */
    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }

    /** @return HasMany<LessonActivity, $this> */
    public function activities()
    {
        return $this->hasMany(LessonActivity::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<LessonProgress, $this> */
    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
