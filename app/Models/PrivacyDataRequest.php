<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyDataRequest extends Model
{
    protected $fillable = [
        'user_id', 'request_type', 'status', 'description', 'admin_notes',
        'reviewed_by', 'identity_verified_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'identity_verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
