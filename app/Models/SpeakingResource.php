<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Resource as ContentResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/** @property-read ContentResource|null $resource */
class SpeakingResource extends Model
{
    use HasTranslations;

    protected $fillable = [
        'resource_id', 'prompt', 'target_skill', 'preparation_time_seconds', 'response_time_seconds',
        'instructions', 'response_expectation', 'success_criteria', 'reflection_prompt',
    ];

    protected $attributes = [
        'preparation_time_seconds' => 30,
        'response_time_seconds' => 90,
    ];

    protected $casts = [
        'preparation_time_seconds' => 'integer',
        'response_time_seconds' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (SpeakingResource $speaking): void {
            $issues = $speaking->publicationIssues();
            if ($issues !== []) {
                throw ValidationException::withMessages(['speakingResource' => implode(' ', $issues)]);
            }
        });
    }

    /** @return array<int, string> */
    public function publicationIssues(): array
    {
        $issues = [];
        foreach (['prompt', 'target_skill', 'instructions', 'response_expectation', 'success_criteria', 'reflection_prompt'] as $field) {
            if (blank($this->getAttribute($field))) {
                $issues[] = str($field)->replace('_', ' ')->ucfirst().' is required.';
            }
        }
        $preparationTime = (int) $this->getAttribute('preparation_time_seconds');
        $responseTime = (int) $this->getAttribute('response_time_seconds');

        if ($preparationTime < 0 || $preparationTime > 300) {
            $issues[] = 'Preparation time must be between 0 and 300 seconds.';
        }
        if ($responseTime < 15 || $responseTime > 600) {
            $issues[] = 'Response time must be between 15 and 600 seconds.';
        }

        return $issues;
    }

    /** @return BelongsTo<ContentResource, $this> */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(ContentResource::class);
    }
}
