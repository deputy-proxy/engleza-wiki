<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property-read Level|null $level
 * @property-read Collection<int, LearningUnit> $units
 */
class LearningPath extends Model
{
    use HasTranslations;

    protected $fillable = ['level_id', 'title', 'slug', 'description', 'status', 'sort_order', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (LearningPath $path): void {
            if ($path->status !== 'published') {
                return;
            }
            if ($path->published_at === null) {
                throw ValidationException::withMessages(['published_at' => 'A publication date is required.']);
            }

            $units = $path->units()->where('is_published', true)
                ->with(['lessons.activities.resource', 'lessons.activities.test'])->get();
            $hasPublishedLesson = $units->flatMap(fn (LearningUnit $unit) => $unit->lessons)
                ->contains(fn (LearningLesson $lesson): bool => $lesson->status === 'published' && in_array($lesson->editorial_status, ['approved', 'grandfathered'], true) && $lesson->publicationIssues() === []);

            if (! $hasPublishedLesson) {
                throw ValidationException::withMessages(['status' => 'Publish at least one unit containing a published lesson with valid activities before publishing this path.']);
            }
        });
    }

    /** @return BelongsTo<Level, $this> */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /** @return HasMany<LearningUnit, $this> */
    public function units()
    {
        return $this->hasMany(LearningUnit::class)->orderBy('sort_order')->orderBy('id');
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

    /** @param Builder<LearningPath> $query
     * @return Builder<LearningPath>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereHas('units.lessons', fn (Builder $lessons) => $lessons->where('status', 'published')->whereIn('editorial_status', ['approved', 'grandfathered']));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
