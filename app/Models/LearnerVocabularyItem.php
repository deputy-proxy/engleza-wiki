<?php

namespace App\Models;

use App\Models\Resource as ResourceModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearnerVocabularyItem extends Model
{
    protected $fillable = [
        'user_id', 'resource_id', 'source_id', 'source_type', 'item_key', 'prompt', 'answer',
        'interval_seconds', 'ease_factor', 'repetitions', 'lapses', 'last_grade',
        'last_reviewed_at', 'due_at',
    ];

    protected function casts(): array
    {
        return [
            'ease_factor' => 'float',
            'last_reviewed_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ResourceModel, $this> */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /** @return HasMany<VocabularyReviewEvent, $this> */
    public function reviews()
    {
        return $this->hasMany(VocabularyReviewEvent::class);
    }
}
