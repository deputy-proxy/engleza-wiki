<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/** @property-read \App\Models\Resource|null $resource */
class WritingResource extends Model
{
    use HasTranslations;

    protected $fillable = ['resource_id', 'prompt', 'instructions', 'minimum_words', 'maximum_words', 'recommended_time_minutes', 'rubric', 'example_answer'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function submissions()
    {
        return $this->hasMany(WritingSubmission::class);
    }
}
