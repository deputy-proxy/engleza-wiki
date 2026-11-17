<?php

namespace App\Services\Assessment;

use Illuminate\Support\Collection;

class QuestionScoringService
{
    /**
     * @param  Collection<int, mixed>  $questions
     * @param  Collection<int, mixed>  $answers
     * @return array{earned_points: float, total_points: float, percentage: float, question_scores: array<int, array{question_id: int, earned_points: float, total_points: float, is_correct: bool, section: string, topics: array<int, array{id: int, name: string, slug: string}>}>, by_section: array<string, array{earned: float, total: float}>, by_topic: array<int, array{earned: float, total: float, name: string, slug: string}>, breakdown: array{sections: array<string, array{earned_points: float, total_points: float, percentage: float}>, topics: array<int, array{name: string, slug: string, earned_points: float, total_points: float, percentage: float}>}}
     */
    public function score(Collection $questions, Collection $answers): array
    {
        $answersByQuestion = $answers->keyBy('question_id');
        $earnedPoints = 0.0;
        $totalPoints = 0.0;
        $questionScores = [];
        $bySection = [];
        $byTopic = [];

        foreach ($questions as $question) {
            $questionId = (int) $question->id;
            $points = (float) $question->points;
            $answer = $answersByQuestion->get($questionId);
            $correct = $answer !== null
                && $answer->option !== null
                && (int) $answer->option->question_id === $questionId
                && (bool) $answer->option->is_correct;
            $earned = $correct ? $points : 0.0;
            $section = $question->section->title ?? 'General';

            $totalPoints += $points;
            $earnedPoints += $earned;
            $questionScores[$questionId] = [
                'question_id' => $questionId,
                'earned_points' => $earned,
                'total_points' => $points,
                'is_correct' => $correct,
                'section' => $section,
                'topics' => $question->topics->map(fn ($topic): array => [
                    'id' => (int) $topic->id,
                    'name' => $topic->name,
                    'slug' => $topic->slug,
                ])->all(),
            ];

            $bySection[$section] ??= ['earned' => 0.0, 'total' => 0.0];
            $bySection[$section]['total'] += $points;
            $bySection[$section]['earned'] += $earned;

            foreach ($question->topics as $topic) {
                $topicId = (int) $topic->id;
                $byTopic[$topicId] ??= ['earned' => 0.0, 'total' => 0.0, 'name' => $topic->name, 'slug' => $topic->slug];
                $byTopic[$topicId]['total'] += $points;
                $byTopic[$topicId]['earned'] += $earned;
            }
        }

        $sections = [];
        foreach ($bySection as $name => $section) {
            $sections[$name] = [
                'earned_points' => $section['earned'],
                'total_points' => $section['total'],
                'percentage' => $section['total'] > 0 ? round(($section['earned'] / $section['total']) * 100, 1) : 0.0,
            ];
        }

        $topics = [];
        foreach ($byTopic as $id => $topic) {
            $topics[$id] = [
                'name' => $topic['name'], 'slug' => $topic['slug'],
                'earned_points' => $topic['earned'], 'total_points' => $topic['total'],
                'percentage' => $topic['total'] > 0 ? round(($topic['earned'] / $topic['total']) * 100, 1) : 0.0,
            ];
        }

        return [
            'earned_points' => $earnedPoints,
            'total_points' => $totalPoints,
            'percentage' => $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0.0,
            'question_scores' => $questionScores,
            'by_section' => $bySection,
            'by_topic' => $byTopic,
            'breakdown' => ['sections' => $sections, 'topics' => $topics],
        ];
    }
}
