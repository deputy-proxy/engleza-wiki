<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, int> $question_order
 * @property-read Carbon|null $completed_at
 */
class AdaptivePracticeSession extends Model
{
    protected $fillable = [
        'user_id', 'question_order', 'status', 'difficulty_min', 'difficulty_max',
        'earned_points', 'total_points', 'percentage', 'started_at', 'completed_at',
    ];

    /** @return array<int, int> */
    public function questionIds(): array
    {
        $decoded = json_decode((string) $this->getRawOriginal('question_order'), true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map('intval', $decoded));
    }

    protected function casts(): array
    {
        return [
            'question_order' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AdaptivePracticeAnswer, $this> */
    public function answers()
    {
        return $this->hasMany(AdaptivePracticeAnswer::class);
    }
}
