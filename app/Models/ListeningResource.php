<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Resource as ContentResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read ContentResource|null $resource
 * @property-read Collection<int, Question> $questions
 */
class ListeningResource extends Model
{
    use HasTranslations;

    protected $casts = ['show_correct_answers' => 'boolean'];

    protected $fillable = ['resource_id', 'instructions', 'show_correct_answers', 'media_path', 'media_type', 'original_filename', 'mime_type', 'file_size', 'duration_seconds', 'media_source_url', 'media_license', 'media_attribution', 'media_permission_reference'];

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

    public function mediaUrl(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }
}
