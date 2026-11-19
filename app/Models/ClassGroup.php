<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property-read User|null $teacher
 * @property-read Test|null $test
 */
class ClassGroup extends Model
{
    protected $fillable = ['name', 'join_code', 'teacher_id', 'test_id', 'description', 'is_active'];

    protected $casts = ['is_active' => 'bool'];

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw ValidationException::withMessages(['class' => 'Classes must be archived rather than deleted.']);
        });

        static::creating(function (self $g) {
            if (empty($g->join_code)) {
                $g->join_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('join_code', $code)->exists());

        return $code;
    }

    /** @return HasMany<ClassGroupMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(ClassGroupMembership::class);
    }

    /** @return HasMany<ClassGroupMembership, $this> */
    public function activeMemberships(): HasMany
    {
        return $this->memberships()->whereNull('removed_at');
    }

    /** @return HasMany<ClassGroupTransfer, $this> */
    public function transfers(): HasMany
    {
        return $this->hasMany(ClassGroupTransfer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return BelongsTo<Test, $this> */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    /** @return BelongsToMany<WritingResource, $this> */
    public function writingResources()
    {
        return $this->belongsToMany(WritingResource::class, 'class_group_writing_resource')->withTimestamps();
    }

    /** @return HasMany<WritingSubmission, $this> */
    public function writingSubmissions(): HasMany
    {
        return $this->hasMany(WritingSubmission::class);
    }

    /** @return HasMany<TestAttempt, $this> */
    public function activeAttempts(): HasMany
    {
        return $this->attempts()->whereNotNull('user_id')
            ->whereHas('user.classGroupMemberships', fn ($query) => $query->whereColumn('class_group_memberships.class_group_id', 'test_attempts.class_group_id')->whereNull('removed_at'));
    }

    public function getRouteKeyName(): string
    {
        return 'join_code';
    }
}
