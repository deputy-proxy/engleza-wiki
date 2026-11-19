<?php

namespace App\Models\Concerns;

use App\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /** @return MorphMany<ContentTranslation, $this> */
    public function translations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    /** Return an approved or grandfathered translation only while its source text is unchanged. */
    public function translated(string $field, ?string $locale = null): mixed
    {
        $locale ??= App::getLocale();
        $source = $this->getAttribute($field);

        if ($locale === 'en' || $source === null || $source === '') {
            return $source;
        }

        $sourceHash = $this->translationSourceHash($source);
        $translations = $this->getRelationValue('localizedFields');

        if (! $translations instanceof Collection) {
            $translations = $this->relationLoaded('translations')
                ? $this->getRelation('translations')->where('locale', $locale)->where('status', 'reviewed')->whereIn('editorial_status', ['approved', 'grandfathered'])->keyBy('field')
                : $this->translations()->where('locale', $locale)->where('status', 'reviewed')->whereIn('editorial_status', ['approved', 'grandfathered'])->get()->keyBy('field');
            $this->setRelation('localizedFields', $translations);
        }

        $translation = $translations->get($field);
        if (! $translation || ! hash_equals((string) $translation->source_hash, $sourceHash)) {
            return $source;
        }

        if (is_array($source)) {
            $decoded = json_decode($translation->value, true);

            return is_array($decoded) ? $decoded : $source;
        }

        return $translation->value;
    }

    public function translationStatus(string $field, ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        if ($locale === 'en') {
            return 'source';
        }

        $source = $this->getAttribute($field);
        $translation = $this->translations()->where('locale', $locale)->where('field', $field)->first();
        if (! $translation) {
            return 'missing';
        }

        if ($translation->status === 'reviewed' && ! hash_equals((string) $translation->source_hash, $this->translationSourceHash($source))) {
            return 'stale';
        }

        if ($translation->editorial_status === 'grandfathered') {
            return 'grandfathered';
        }

        if ($translation->editorial_status !== 'approved') {
            return 'editorial_'.$translation->editorial_status;
        }

        return $translation->status;
    }

    private function translationSourceHash(mixed $source): string
    {
        $value = is_array($source)
            ? json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : (string) $source;

        return hash('sha256', $value);
    }
}
