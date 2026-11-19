<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read \App\Models\Resource|null $resource */
class WritingResource extends Model
{
    use HasTranslations;

    protected $fillable = ['resource_id', 'prompt', 'instructions', 'minimum_words', 'maximum_words', 'recommended_time_minutes', 'rubric', 'example_answer'];

    /** @return BelongsTo<\App\Models\Resource, $this> */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /** @return HasMany<WritingSubmission, $this> */
    public function submissions()
    {
        return $this->hasMany(WritingSubmission::class);
    }

    /** @return BelongsToMany<ClassGroup, $this> */
    public function classGroups()
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_writing_resource')->withTimestamps();
    }
}
