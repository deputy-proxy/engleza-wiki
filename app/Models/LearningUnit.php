<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read Collection<int, LearningLesson> $lessons */
class LearningUnit extends Model
{
    use HasTranslations;

    protected $fillable = ['learning_path_id', 'title', 'description', 'sort_order', 'is_published'];

    protected $casts = ['is_published' => 'bool'];

    /** @return BelongsTo<LearningPath, $this> */
    public function path()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    /** @return HasMany<LearningLesson, $this> */
    public function lessons()
    {
        return $this->hasMany(LearningLesson::class)->orderBy('sort_order')->orderBy('id');
    }
}
