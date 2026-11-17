<?php

namespace App\Services\Test;

use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\DB;

class TestQuestionOrderService
{
    /** @return array<int, int> */
    public function forTest(Test $test): array
    {
        $ids = $test->allQuestions()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if ($test->shuffle_questions) {
            shuffle($ids);
        }

        return $ids;
    }

    /** @return array<int, int> */
    public function forAttempt(TestAttempt $attempt): array
    {
        if ($attempt->question_order !== null) {
            return $attempt->question_order;
        }
        if (in_array($attempt->status, ['completed', 'expired'], true)) {
            return [];
        }

        return DB::transaction(function () use ($attempt): array {
            $locked = TestAttempt::query()->with('test')->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($locked->question_order === null) {
                $locked->forceFill(['question_order' => $this->forTest($locked->test)])->save();
            }
            $attempt->setAttribute('question_order', $locked->question_order);

            return $locked->question_order ?? [];
        });
    }
}
