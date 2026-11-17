<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read TestSection|null $section
 * @property-read ReadingResource|null $readingResource
 * @property-read ListeningResource|null $listeningResource
 * @property-read Collection<int, QuestionOption> $options
 * @property-read Collection<int, Topic> $topics
 */
class Question extends Model
{
    use HasFactory;

    protected $fillable = ['test_section_id', 'listening_resource_id', 'reading_resource_id', 'type', 'question', 'explanation', 'points', 'difficulty', 'sort_order', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    /** @return BelongsTo<TestSection, $this> */
    public function section()
    {
        return $this->belongsTo(TestSection::class, 'test_section_id');
    }

    /** @return BelongsTo<ListeningResource, $this> */
    public function listeningResource()
    {
        return $this->belongsTo(ListeningResource::class);
    }

    /** @return BelongsTo<ReadingResource, $this> */
    public function readingResource()
    {
        return $this->belongsTo(ReadingResource::class);
    }

    /** @return HasMany<QuestionOption, $this> */
    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    /** @return BelongsToMany<Topic, $this> */
    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }
}
