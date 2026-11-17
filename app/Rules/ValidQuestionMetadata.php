<?php

namespace App\Rules;

use App\Services\Assessment\QuestionResponseService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidQuestionMetadata implements ValidationRule
{
    public function __construct(private readonly string $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($this->type, ['matching', 'ordering', 'gap_fill'], true)) {
            return;
        }

        $metadata = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($metadata)) {
            $fail('Exercise configuration must be a JSON object.');

            return;
        }

        foreach (app(QuestionResponseService::class)->metadataIssues($this->type, $metadata) as $issue) {
            $fail($issue);
        }
    }
}
