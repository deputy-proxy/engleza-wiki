<?php

namespace App\Models\Concerns;

use App\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /** @return MorphMany<ContentTranslation, $this> */
    public function translations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    /**
     * Return a reviewed translation, falling back to the unchanged source field.
     */
    public function translated(string $field, ?string $locale = null): mixed
    {
        $locale ??= App::getLocale();

        if ($locale === 'en') {
            return $this->getAttribute($field);
        }

        $translations = $this->getRelationValue('localizedFields');

        if (! is_array($translations)) {
            $translations = ContentTranslation::query()
                ->where('translatable_type', $this->getMorphClass())
                ->where('translatable_id', $this->getKey())
                ->where('locale', $locale)
                ->where('status', 'reviewed')
                ->pluck('value', 'field')
                ->all();

            $this->setRelation('localizedFields', $translations);
        }

        return $translations[$field] ?? $this->getAttribute($field);
    }

    public function translationStatus(string $field, ?string $locale = null): string
    {
        $locale ??= App::getLocale();

        if ($locale === 'en') {
            return 'source';
        }

        return ContentTranslation::query()
            ->where('translatable_type', $this->getMorphClass())
            ->where('translatable_id', $this->getKey())
            ->where('locale', $locale)
            ->where('field', $field)
            ->value('status') ?? 'missing';
    }
}
