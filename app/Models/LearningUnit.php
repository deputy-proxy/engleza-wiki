<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningUnit extends Model
{
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
