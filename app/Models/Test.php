<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Level|null $level
 * @property-read Skill|null $skill
 * @property-read Collection<int, TestSection> $sections
 * @property-read Collection<int, TestAttempt> $attempts
 */
class Test extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'instructions', 'level_id', 'skill_id', 'duration_minutes', 'passing_score', 'access_type', 'status', 'is_public', 'shuffle_questions', 'show_correct_answers', 'published_at'];

    protected $casts = ['is_public' => 'bool', 'shuffle_questions' => 'bool', 'show_correct_answers' => 'bool', 'published_at' => 'datetime'];

    /** @return BelongsTo<Level, $this> */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /** @return BelongsTo<Skill, $this> */
    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    /** @return HasMany<TestSection, $this> */
    public function sections()
    {
        return $this->hasMany(TestSection::class)->orderBy('sort_order');
    }

    /** @return HasMany<TestAttempt, $this> */
    public function attempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return Collection<int, Question> */
    public function allQuestions(): Collection
    {
        return Question::whereIn('test_section_id', $this->sections()->pluck('id'))->orderBy('sort_order')->get();
    }
}
