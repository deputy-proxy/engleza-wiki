<?php

namespace App\Services\Assessment;

use App\Models\Question;
use App\Models\QuestionOption;
use InvalidArgumentException;

class QuestionResponseService
{
    /** @param array<string, scalar> $replace */
    private function message(string $key, array $replace = []): string
    {
        if (function_exists('app') && app()->bound('translator')) {
            return __($key, $replace);
        }

        foreach ($replace as $name => $value) {
            $key = str_replace(':'.$name, (string) $value, $key);
        }

        return $key;
    }

    public const SUPPORTED_TYPES = [
        'single_choice', 'multiple_choice', 'true_false', 'text', 'short_text',
        'matching', 'ordering', 'gap_fill',
    ];

    /** @return array<int, string> */
    public function metadataIssues(string $type, mixed $metadata): array
    {
        if (! in_array($type, ['matching', 'ordering', 'gap_fill'], true)) {
            return [];
        }
        if (! is_array($metadata)) {
            return [$this->message('Exercise configuration must be a JSON object.')];
        }

        if ($type === 'matching') {
            $pairs = $metadata['pairs'] ?? null;
            if (! is_array($pairs) || ! array_is_list($pairs) || count($pairs) < 2) {
                return [$this->message('Matching exercises require at least two pairs.')];
            }
            $left = [];
            $right = [];
            foreach ($pairs as $pair) {
                if (! is_array($pair) || ! is_string($pair['left'] ?? null) || trim($pair['left']) === ''
                    || ! is_string($pair['right'] ?? null) || trim($pair['right']) === '') {
                    return [$this->message('Every matching pair requires non-empty left and right values.')];
                }
                $left[] = trim($pair['left']);
                $right[] = trim($pair['right']);
            }
            if (count(array_unique($left)) !== count($left) || count(array_unique($right)) !== count($right)) {
                return [$this->message('Matching pair labels and matches must be unique.')];
            }

            return [];
        }

        if ($type === 'ordering') {
            $items = $metadata['items'] ?? null;
            if (! is_array($items) || ! array_is_list($items) || count($items) < 2
                || ! collect($items)->every(fn ($item): bool => is_string($item) && trim($item) !== '')
                || count(array_unique($items)) !== count($items)) {
                return [$this->message('Ordering exercises require at least two unique, non-empty items in the correct order.')];
            }

            return [];
        }

        $gaps = $metadata['gaps'] ?? null;
        if (! is_array($gaps) || $gaps === []) {
            return [$this->message('Gap-fill exercises require at least one named gap.')];
        }
        foreach ($gaps as $key => $accepted) {
            if (! is_string($key) || trim($key) === '') {
                return [$this->message('Every gap requires a non-empty name.')];
            }
            $answersAreValid = is_array($accepted)
                ? array_is_list($accepted) && $accepted !== [] && collect($accepted)->every(fn ($answer): bool => is_string($answer) && trim($answer) !== '')
                : is_string($accepted) && trim($accepted) !== '';
            if (! $answersAreValid) {
                return [$this->message('Every gap requires one or more non-empty accepted answers.')];
            }
        }

        return [];
    }

    /** @return array<int, string> */
    public function definitionIssues(Question $question): array
    {
        if (! in_array($question->type, self::SUPPORTED_TYPES, true)) {
            return [$this->message('Unsupported question type [:type].', ['type' => $question->type])];
        }
        if (in_array($question->type, ['matching', 'ordering', 'gap_fill'], true)) {
            return $this->metadataIssues($question->type, $question->metadata);
        }

        $options = $question->options;
        $optionsAreComplete = $options->every(fn ($option): bool => trim($option->value) !== '');
        $correctOptions = $options->where('is_correct', true);
        $correctCount = $correctOptions->count();

        if (in_array($question->type, ['text', 'short_text'], true)) {
            return $correctCount >= 1 && $correctOptions->every(fn ($option): bool => trim($option->value) !== '')
                ? [] : [$this->message('Text questions require at least one non-empty accepted answer option marked correct.')];
        }

        return match ($question->type) {
            'single_choice' => $optionsAreComplete && $options->count() >= 2 && $correctCount === 1 ? [] : [$this->message('Single-choice questions require at least two options and exactly one correct option.')],
            'true_false' => $optionsAreComplete && $options->count() === 2 && $correctCount === 1 ? [] : [$this->message('True/false questions require exactly two options and exactly one correct option.')],
            default => $optionsAreComplete && $options->count() >= 2 && $correctCount >= 1 ? [] : [$this->message('Multiple-choice questions require at least two options and at least one correct option.')],
        };
    }

    /**
     * @return array{valid: bool, correct: bool, earned_points: float, total_points: float, normalized: mixed}
     */
    public function evaluate(Question $question, mixed $response): array
    {
        if (! in_array($question->type, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException($this->message('Unsupported question type [:type].', ['type' => $question->type]));
        }
        $points = (float) $question->points;
        if ($this->definitionIssues($question) !== []) {
            return ['valid' => false, 'correct' => false, 'earned_points' => 0.0, 'total_points' => $points, 'normalized' => null];
        }

        $valid = false;
        $correct = false;
        $normalized = null;
        $fraction = 0.0;

        switch ($question->type) {
            case 'single_choice':
            case 'true_false':
                $optionId = filter_var($response, FILTER_VALIDATE_INT);
                $option = $optionId === false ? null : $question->options->firstWhere('id', $optionId);
                $valid = $option instanceof QuestionOption;
                $correct = $valid && (bool) $option->is_correct;
                $normalized = $valid ? (int) $option->id : null;
                $fraction = $correct ? 1.0 : 0.0;
                break;

            case 'multiple_choice':
                $rawIds = is_array($response) ? $response : [];
                $idsAreScalar = collect($rawIds)->every(fn ($id): bool => is_int($id) || (is_string($id) && ctype_digit($id)));
                $ids = $idsAreScalar ? array_values(array_unique(array_map('intval', $rawIds))) : [];
                $valid = is_array($response) && $idsAreScalar && count($ids) === count($response)
                    && ($ids === [] || $question->options->whereIn('id', $ids)->count() === count($ids));
                $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id): int => (int) $id)->all();
                $selectedCorrect = count(array_intersect($ids, $correctIds));
                $selectedIncorrect = count($ids) - $selectedCorrect;
                $correct = $valid && count($ids) === count($correctIds) && $selectedCorrect === count($correctIds);
                $normalized = $valid ? $ids : null;
                $fraction = $valid && count($correctIds) > 0
                    ? max(0, $selectedCorrect - $selectedIncorrect) / count($correctIds) : 0.0;
                break;

            case 'text':
            case 'short_text':
                $valid = is_string($response) && mb_strlen($response) <= 2000;
                $normalized = $valid ? $this->normalizeText($response) : null;
                $accepted = $question->options->where('is_correct', true)->pluck('value')->all();
                $correct = $valid && in_array($normalized, array_map(fn ($value): string => $this->normalizeText((string) $value), $accepted), true);
                $fraction = $correct ? 1.0 : 0.0;
                break;

            case 'matching':
                $pairs = $question->metadata['pairs'] ?? [];
                $pairsAreValid = is_array($pairs) && array_is_list($pairs) && $pairs !== []
                    && collect($pairs)->every(fn ($pair): bool => is_array($pair) && is_string($pair['left'] ?? null) && is_string($pair['right'] ?? null));
                $leftKeys = $pairsAreValid ? array_map(fn ($pair): string => $pair['left'], $pairs) : [];
                $responseValuesAreValid = is_array($response) && collect($response)->every(fn ($value): bool => is_string($value));
                $valid = $pairsAreValid && $responseValuesAreValid && count(array_diff(array_keys($response), $leftKeys)) === 0;
                $matched = 0;
                if ($valid) {
                    foreach ($pairs as $pair) {
                        if (($response[$pair['left']] ?? null) === $pair['right']) {
                            $matched++;
                        }
                    }
                }
                $normalized = $valid ? $response : null;
                $fraction = $valid ? $matched / count($pairs) : 0.0;
                $correct = $valid && $matched === count($pairs);
                break;

            case 'ordering':
                $items = $question->metadata['items'] ?? [];
                $itemsAreValid = is_array($items) && array_is_list($items) && $items !== []
                    && collect($items)->every(fn ($item): bool => is_string($item));
                $responseValuesAreValid = is_array($response) && collect($response)->every(fn ($item): bool => is_string($item));
                $valid = $itemsAreValid && $responseValuesAreValid
                    && count($response) <= count($items)
                    && count(array_unique($response)) === count($response)
                    && count(array_diff($response, $items)) === 0
                    && count(array_diff(array_keys($response), range(0, count($items) - 1))) === 0;
                $placed = 0;
                if ($valid) {
                    foreach ($response as $position => $item) {
                        if (($items[$position] ?? null) === $item) {
                            $placed++;
                        }
                    }
                }
                $normalized = $valid ? $response : null;
                $fraction = $valid ? $placed / count($items) : 0.0;
                $correct = $valid && $placed === count($items);
                break;

            case 'gap_fill':
                $gaps = $question->metadata['gaps'] ?? [];
                $gapsAreValid = is_array($gaps) && $gaps !== [] && collect($gaps)->every(fn ($accepted): bool => (is_string($accepted) || (is_array($accepted) && collect($accepted)->every(fn ($value): bool => is_string($value)))));
                $responseValuesAreValid = is_array($response) && collect($response)->every(fn ($value): bool => is_string($value));
                $valid = $gapsAreValid && $responseValuesAreValid && count(array_diff(array_keys($response), array_keys($gaps))) === 0;
                $filled = 0;
                if ($valid) {
                    foreach ($gaps as $key => $accepted) {
                        $answers = is_array($accepted) ? $accepted : [$accepted];
                        if (isset($response[$key]) && in_array($this->normalizeText($response[$key]), array_map(fn ($value): string => $this->normalizeText((string) $value), $answers), true)) {
                            $filled++;
                        }
                    }
                }
                $normalized = $valid ? $response : null;
                $fraction = $valid ? $filled / count($gaps) : 0.0;
                $correct = $valid && $filled === count($gaps);
                break;
        }

        return [
            'valid' => $valid,
            'correct' => $correct,
            'earned_points' => round($points * max(0.0, min(1.0, $fraction)), 2),
            'total_points' => $points,
            'normalized' => $normalized,
        ];
    }

    /** @return array<string, mixed> */
    public function learnerConfiguration(Question $question): array
    {
        $metadata = $question->metadata ?? [];

        return match ($question->type) {
            'matching' => $this->matchingConfiguration($question, $metadata),
            'ordering' => ['items' => $this->stableOrder(is_array($metadata['items'] ?? null) ? $metadata['items'] : [], (string) $question->getKey())],
            'gap_fill' => ['gaps' => array_fill_keys(array_keys(is_array($metadata['gaps'] ?? null) ? $metadata['gaps'] : []), '')],
            default => [],
        };
    }

    /** @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function matchingConfiguration(Question $question, array $metadata): array
    {
        $pairs = collect(is_array($metadata['pairs'] ?? null) ? $metadata['pairs'] : [])
            ->filter(fn ($pair): bool => is_array($pair) && is_string($pair['left'] ?? null) && is_string($pair['right'] ?? null))
            ->values();
        $choices = $this->stableOrder($pairs->pluck('right')->all(), (string) $question->getKey());

        return ['pairs' => $pairs->map(fn ($pair): array => [
            'left' => $pair['left'],
            'choices' => $choices,
        ])->all()];
    }

    /** @param array<int, mixed> $values
     * @return array<int, mixed>
     */
    private function stableOrder(array $values, string $seed): array
    {
        return collect($values)->sortBy(fn ($value): string => hash('sha256', $seed.'|'.serialize($value)))
            ->values()->all();
    }

    /** @return array<string, mixed> */
    public function answerKey(Question $question): array
    {
        return match ($question->type) {
            'single_choice', 'true_false', 'multiple_choice' => [
                'correct_option_ids' => $question->options->where('is_correct', true)->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            ],
            'text', 'short_text' => [
                'accepted_answers' => $question->options->where('is_correct', true)->pluck('value')->values()->all(),
            ],
            'matching', 'ordering', 'gap_fill' => $question->metadata ?? [],
            default => [],
        };
    }

    private function normalizeText(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value)));
    }
}
