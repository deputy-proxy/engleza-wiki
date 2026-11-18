<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Resource as ContentResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read ContentResource $resource
 * @property-read Collection<int, Question> $questions
 */
class ReadingResource extends Model
{
    use HasTranslations;

    protected $casts = ['show_correct_answers' => 'boolean'];

    protected $fillable = ['resource_id', 'title', 'instructions', 'content', 'show_correct_answers'];

    /** @return BelongsTo<ContentResource, $this> */
    public function resource()
    {
        return $this->belongsTo(ContentResource::class);
    }

    /** @return HasMany<Question, $this> */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }
}
