<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ClassGroupMembership extends Model
{
    protected $fillable = ['class_group_id', 'user_id', 'added_by', 'joined_at', 'removed_by', 'removed_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'removed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw ValidationException::withMessages(['membership' => 'Membership history is retained. Remove a learner by setting a removal date instead.']);
        });
    }

    /** @return BelongsTo<ClassGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
