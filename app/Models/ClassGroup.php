<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ClassGroup extends Model
{
    protected $fillable = ['name', 'join_code', 'teacher_id', 'test_id', 'description', 'is_active'];

    protected $casts = ['is_active' => 'bool'];

    protected static function booted(): void
    {
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

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function test()
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

    public function getRouteKeyName(): string
    {
        return 'join_code';
    }
}
