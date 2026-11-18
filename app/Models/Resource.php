<?php

namespace App\Models;

use App\Contracts\EditorialReviewable;
use App\Models\Concerns\HasEditorialReview;
use App\Models\Concerns\HasTranslations;
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
 * @property-read Collection<int, Media> $media
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, Audience> $audiences
 * @property string $editorial_status
 * @property int|null $editorial_reviewed_by
 * @property-read CardResource|null $cardResource
 */
class Resource extends Model implements EditorialReviewable
{
    use HasEditorialReview;
    use HasFactory;
    use HasTranslations;

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

    /** @return BelongsToMany<Category, $this> */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    /** @return BelongsToMany<Audience, $this> */
    public function audiences()
    {
        return $this->belongsToMany(Audience::class);
    }

    /** @return BelongsToMany<Topic, $this> */
    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }

    /** @return HasMany<Media, $this> */
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
        return $q->where('status', 'published')->where('published_at', '<=', now())->editorialVisible();
    }

    /** @return array<int, string> */
    public function editorialReviewableFields(): array
    {
        return ['title', 'slug', 'excerpt', 'content', 'type', 'level_id', 'published_at', 'seo_title', 'seo_description', 'canonical_url', 'robots_index', 'og_title', 'og_description', 'og_image'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
