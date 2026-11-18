<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperiencePointAward extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'source_type', 'source_id', 'idempotency_key', 'points',
        'activity_date', 'occurred_at', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'activity_date' => 'immutable_date',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
