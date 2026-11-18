<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function resources()
    {
        return $this->hasMany(Resource::class, 'author_id');
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    /** @return HasMany<LearningPathEnrollment, $this> */
    public function learningPathEnrollments()
    {
        return $this->hasMany(LearningPathEnrollment::class);
    }

    /** @return HasMany<LessonProgress, $this> */
    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    /** @return HasMany<LearnerVocabularyItem, $this> */
    public function vocabularyItems()
    {
        return $this->hasMany(LearnerVocabularyItem::class);
    }

    /** @return HasMany<AdaptivePracticeSession, $this> */
    public function adaptivePracticeSessions()
    {
        return $this->hasMany(AdaptivePracticeSession::class);
    }

    /** @return HasMany<ExperiencePointAward, $this> */
    public function experiencePointAwards()
    {
        return $this->hasMany(ExperiencePointAward::class);
    }

    /** @return HasMany<LearnerAchievement, $this> */
    public function achievements()
    {
        return $this->hasMany(LearnerAchievement::class);
    }

    public function savedResources()
    {
        return $this->hasMany(SavedResource::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
