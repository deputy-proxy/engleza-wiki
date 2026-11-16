<?php

namespace App\Services\Test;

use App\Models\AssessmentResult;
use App\Models\Resource;
use App\Models\TestAnswer;
use App\Models\TestAttempt;

class TestScoringService
{
    public function score(TestAttempt $attempt): AssessmentResult
    {
        $answers = TestAnswer::with('question.section', 'question.topics', 'option')->where('test_attempt_id', $attempt->id)->get();
        $totalPoints = 0;
        $earnedPoints = 0;
        $bySkill = []; // skillName => ['earned'=>x,'total'=>y]
        $byTopic = []; // topicId => ['earned'=>x,'total'=>y,'name'=>...]

        foreach ($answers as $ans) {
            $q = $ans->question;
            if (! $q) {
                continue;
            }
            $points = $q->points;
            $totalPoints += $points;

            $correct = false;
            if ($ans->option && $ans->option->is_correct) {
                $correct = true;
            }
            $ans->is_correct = $correct;
            $ans->points_awarded = $correct ? $points : 0;
            $ans->save();
            if ($correct) {
                $earnedPoints += $points;
            }

            $skillName = $q->section?->title ?? 'General';
            $bySkill[$skillName] ??= ['earned' => 0, 'total' => 0];
            $bySkill[$skillName]['total'] += $points;
            if ($correct) {
                $bySkill[$skillName]['earned'] += $points;
            }

            foreach ($q->topics as $t) {
                $byTopic[$t->id] ??= ['earned' => 0, 'total' => 0, 'name' => $t->name, 'slug' => $t->slug];
                $byTopic[$t->id]['total'] += $points;
                if ($correct) {
                    $byTopic[$t->id]['earned'] += $points;
                }
            }
        }

        $pct = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;
        $level = $this->estimateLevel($pct, $attempt->test->level?->slug);

        $skillPct = fn ($k) => isset($bySkill[$k]) && $bySkill[$k]['total'] > 0 ? round(($bySkill[$k]['earned'] / $bySkill[$k]['total']) * 100, 1) : null;

        // Recommendations: find weak topics (<70%), find matching published resources
        $weakTopicIds = [];
        foreach ($byTopic as $id => $d) {
            $p = $d['total'] > 0 ? ($d['earned'] / $d['total']) * 100 : 100;
            if ($p < 70) {
                $weakTopicIds[] = $id;
            }
        }
        $recIds = Resource::published()
            ->whereHas('topics', fn ($q) => $q->whereIn('topics.id', $weakTopicIds))
            ->limit(6)->pluck('id')->toArray();
        $recommendation = json_encode(['weak_topics' => array_values(array_map(fn ($id) => $byTopic[$id]['name'], $weakTopicIds)), 'resource_ids' => $recIds]);

        $attempt->score = $earnedPoints;
        $attempt->percentage = $pct;
        $attempt->level_result = $level;
        $attempt->status = 'completed';
        $attempt->completed_at = now();
        if ($attempt->started_at) {
            $attempt->time_spent_seconds = $attempt->started_at->diffInSeconds(now());
        }
        $attempt->save();

        $result = AssessmentResult::updateOrCreate(
            ['test_attempt_id' => $attempt->id],
            [
                'overall_score' => $earnedPoints, 'overall_percentage' => $pct, 'level' => $level,
                'grammar_score' => $skillPct('Grammar'),
                'vocabulary_score' => $skillPct('Vocabulary'),
                'reading_score' => $skillPct('Reading'),
                'writing_score' => $skillPct('Writing'),
                'listening_score' => $skillPct('Listening'),
                'speaking_score' => $skillPct('Speaking'),
                'recommendation' => $recommendation,
            ]
        );

        return $result;
    }

    private function estimateLevel(float $pct, ?string $testLevelSlug): string
    {
        if ($testLevelSlug) {
            $order = ['pre-a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
            $idx = array_search($testLevelSlug, $order);
            if ($idx === false) {
                $idx = 2;
            }
            if ($pct >= 90 && $idx < count($order) - 1) {
                return strtoupper($order[$idx + 1]);
            }
            if ($pct >= 60) {
                return strtoupper($order[$idx]);
            }
            if ($pct >= 30 && $idx > 0) {
                return strtoupper($order[$idx - 1]);
            }

            return strtoupper($order[max(0, $idx - 1)]);
        }
        if ($pct >= 90) {
            return 'C1';
        }
        if ($pct >= 75) {
            return 'B2';
        }
        if ($pct >= 55) {
            return 'B1';
        }
        if ($pct >= 35) {
            return 'A2';
        }

        return 'A1';
    }
}
