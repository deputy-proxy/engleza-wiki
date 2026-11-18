<?php

namespace App\Models;

use App\Contracts\EditorialReviewable;
use App\Models\Concerns\HasEditorialReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $locale
 * @property string $field
 * @property string $value
 * @property string $status
 * @property string $editorial_status
 * @property int|null $editorial_reviewed_by
 * @property string|null $source_hash
 * @property Carbon|null $reviewed_at
 */
class ContentTranslation extends Model implements EditorialReviewable
{
    use HasEditorialReview;

    protected $fillable = ['translatable_type', 'translatable_id', 'locale', 'field', 'value', 'source_hash', 'status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    /** @return array<int, string> */
    public function editorialReviewableFields(): array
    {
        return ['translatable_type', 'translatable_id', 'locale', 'field', 'value', 'source_hash', 'status'];
    }

    public function approveTranslation(User $reviewer, ?string $notes = null): void
    {
        $this->refresh();
        $this->unsetRelation('translatable');
        $source = $this->translatable?->getAttribute($this->field);
        if ($source === null || $source === '') {
            throw new \InvalidArgumentException('The translation source field no longer exists.');
        }

        $sourceValue = is_array($source)
            ? json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : (string) $source;

        $this->forceFill([
            'status' => 'reviewed',
            'source_hash' => hash('sha256', $sourceValue),
        ])->saveQuietly();
        $this->approveEditorialReview($reviewer, $notes);
    }

    /** @return MorphTo<Model, $this> */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
