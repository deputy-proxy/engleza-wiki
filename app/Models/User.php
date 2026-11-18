<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable implements FilamentUser, MustVerifyEmailContract
{
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if ($user->getOriginal('role') === 'admin' && $user->role !== 'admin') {
                if (auth()->id() === $user->getKey()) {
                    throw ValidationException::withMessages([
                        'role' => 'You cannot demote your own administrator account. Ask another administrator to change your role.',
                    ]);
                }

                if ($user->isLastAdmin()) {
                    throw ValidationException::withMessages([
                        'role' => 'The last active administrator cannot be demoted. Promote another administrator first.',
                    ]);
                }
            }
        });

        static::deleting(function (User $user): void {
            if (auth()->id() === $user->getKey()) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot delete your own account from user management.',
                ]);
            }

            if ($user->isLastAdmin()) {
                throw ValidationException::withMessages([
                    'user' => 'The last active administrator cannot be deleted. Promote another administrator first.',
                ]);
            }

            if ($user->hasProtectedLearningData()) {
                throw ValidationException::withMessages([
                    'user' => 'This account has learning history or class records. Preserve or transfer those records before deleting the account.',
                ]);
            }
        });
    }

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

    /** @return HasMany<PrivacyDataRequest, $this> */
    public function privacyDataRequests()
    {
        return $this->hasMany(PrivacyDataRequest::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLastAdmin(): bool
    {
        return $this->getOriginal('role') === 'admin'
            && static::query()->where('role', 'admin')->count() <= 1;
    }

    /**
     * Check records whose foreign keys would otherwise cascade-delete learner or class history.
     */
    public function hasProtectedLearningData(): bool
    {
        foreach ([
            'test_attempts' => 'user_id',
            'learning_attempts' => 'user_id',
            'learning_path_enrollments' => 'user_id',
            'lesson_progress' => 'user_id',
            'lesson_activity_progress' => 'user_id',
            'learner_vocabulary_items' => 'user_id',
            'vocabulary_review_events' => 'user_id',
            'adaptive_practice_sessions' => 'user_id',
            'experience_point_awards' => 'user_id',
            'learner_achievements' => 'user_id',
            'learner_daily_engagements' => 'user_id',
            'learner_engagement_settings' => 'user_id',
            'saved_resources' => 'user_id',
            'writing_submissions' => 'user_id',
            'class_groups' => 'teacher_id',
            'privacy_data_requests' => 'user_id',
        ] as $table => $column) {
            if (Schema::hasTable($table) && DB::table($table)->where($column, $this->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }
}
