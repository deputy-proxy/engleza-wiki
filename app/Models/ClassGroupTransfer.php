<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ClassGroupTransfer extends Model
{
    protected $fillable = ['class_group_id', 'previous_teacher_id', 'new_teacher_id', 'transferred_by', 'transferred_at'];

    protected function casts(): array
    {
        return ['transferred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw ValidationException::withMessages(['transfer' => 'Class ownership transfer history cannot be deleted.']);
        });
    }
}
