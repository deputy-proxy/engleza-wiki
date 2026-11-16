<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'instructions', 'level_id', 'skill_id', 'duration_minutes', 'passing_score', 'access_type', 'status', 'is_public', 'shuffle_questions', 'show_correct_answers', 'published_at'];

    protected $casts = ['is_public' => 'bool', 'shuffle_questions' => 'bool', 'show_correct_answers' => 'bool', 'published_at' => 'datetime'];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function sections()
    {
        return $this->hasMany(TestSection::class)->orderBy('sort_order');
    }

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

    public function allQuestions()
    {
        return Question::whereIn('test_section_id', $this->sections()->pluck('id'))->orderBy('sort_order')->get();
    }
}
