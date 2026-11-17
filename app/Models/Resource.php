<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read ListeningResource|null $listeningResource
 * @property-read ReadingResource|null $readingResource
 * @property-read WritingResource|null $writingResource
 * @property-read Level|null $level
 * @property-read Collection<int, Skill> $skills
 * @property-read Collection<int, Topic> $topics
 * @property-read CardResource|null $cardResource
 */
class Resource extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'type', 'status', 'level_id', 'author_id', 'featured', 'published_at', 'seo_title', 'seo_description', 'canonical_url', 'robots_index', 'og_title', 'og_description', 'og_image'];

    protected $casts = ['featured' => 'bool', 'robots_index' => 'bool', 'published_at' => 'datetime'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Level, $this> */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function audiences()
    {
        return $this->belongsToMany(Audience::class);
    }

    /** @return BelongsToMany<Topic, $this> */
    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    /** @return HasOne<CardResource, $this> */
    public function cardResource()
    {
        return $this->hasOne(CardResource::class);
    }

    public function cards()
    {
        return $this->hasManyThrough(ResourceCard::class, CardResource::class, 'resource_id', 'card_resource_id');
    }

    /** @return HasOne<ListeningResource, $this> */
    public function listeningResource()
    {
        return $this->hasOne(ListeningResource::class);
    }

    /** @return HasOne<ReadingResource, $this> */
    public function readingResource()
    {
        return $this->hasOne(ReadingResource::class);
    }

    /** @return HasOne<WritingResource, $this> */
    public function writingResource()
    {
        return $this->hasOne(WritingResource::class);
    }

    /** @return HasMany<LearningAttempt, $this> */
    public function learningAttempts()
    {
        return $this->hasMany(LearningAttempt::class);
    }

    /** @return HasMany<LessonActivity, $this> */
    public function lessonActivities()
    {
        return $this->hasMany(LessonActivity::class);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
