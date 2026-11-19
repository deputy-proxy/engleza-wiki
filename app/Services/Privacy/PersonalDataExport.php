<?php

namespace App\Services\Privacy;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PersonalDataExport
{
    /**
     * Export account-owned application records without credentials, raw sessions,
     * private staff notes, or records belonging to other learners.
     *
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $records = [];

        foreach ([
            'test_attempts',
            'learning_attempts',
            'writing_submissions',
            'learning_path_enrollments',
            'lesson_progress',
            'lesson_activity_progress',
            'learner_vocabulary_items',
            'vocabulary_review_events',
            'adaptive_practice_sessions',
            'experience_point_awards',
            'learner_achievements',
            'learner_daily_engagements',
            'learner_engagement_settings',
            'saved_resources',
        ] as $table) {
            $records[$table] = $this->ownedRows($table, 'user_id', $user->getKey());
        }

        $writingSubmissionIds = collect($records['writing_submissions'])->pluck('id')->all();
        $records['writing_submission_versions'] = $this->relatedRows('writing_submission_versions', 'writing_submission_id', $writingSubmissionIds);
        $records['writing_submission_reviews'] = $this->relatedRows('writing_submission_reviews', 'writing_submission_id', $writingSubmissionIds);
        $records['teacher_writing_reviews'] = $this->ownedRows('writing_submission_reviews', 'teacher_id', $user->getKey());

        $testAttemptIds = collect($records['test_attempts'])->pluck('id')->all();
        $learningAttemptIds = collect($records['learning_attempts'])->pluck('id')->all();
        $practiceSessionIds = collect($records['adaptive_practice_sessions'])->pluck('id')->all();

        $records['test_answers'] = $this->relatedRows('test_answers', 'test_attempt_id', $testAttemptIds);
        $records['test_attempt_events'] = $this->relatedRows('test_attempt_events', 'test_attempt_id', $testAttemptIds);
        $records['assessment_results'] = $this->relatedRows('assessment_results', 'test_attempt_id', $testAttemptIds);
        $records['learning_answers'] = $this->relatedRows('learning_answers', 'learning_attempt_id', $learningAttemptIds);
        $records['adaptive_practice_answers'] = $this->relatedRows('adaptive_practice_answers', 'adaptive_practice_session_id', $practiceSessionIds);
        $records['teaching_class_groups'] = $this->ownedRows('class_groups', 'teacher_id', $user->getKey(), [
            'id', 'name', 'test_id', 'description', 'is_active', 'created_at', 'updated_at',
        ]);
        $teachingClassGroupIds = collect($records['teaching_class_groups'])->pluck('id')->all();
        $records['teaching_class_writing_assignments'] = $this->relatedRows('class_group_writing_resource', 'class_group_id', $teachingClassGroupIds);

        $learnerClassGroupIds = collect($records['test_attempts'])->pluck('class_group_id')
            ->merge(collect($records['writing_submissions'])->pluck('class_group_id'))
            ->filter()->unique()->values()->all();
        $records['learner_class_groups'] = $this->relatedRows(
            'class_groups',
            'id',
            $learnerClassGroupIds,
            ['id', 'name', 'test_id', 'description', 'is_active', 'created_at', 'updated_at'],
        );
        $records['privacy_data_requests'] = DB::table('privacy_data_requests')
            ->where('user_id', $user->getKey())
            ->get(['id', 'request_type', 'status', 'description', 'identity_verified_at', 'completed_at', 'created_at', 'updated_at'])
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return [
            'export_version' => '1.1',
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $this->isoDateTime($user->email_verified_at),
                'role' => $user->role,
                'created_at' => $this->isoDateTime($user->created_at),
                'updated_at' => $this->isoDateTime($user->updated_at),
            ],
            'records' => $records,
            'excluded' => [
                'passwords and authentication secrets',
                'raw session payloads and active session identifiers',
                'internal staff notes and other users’ records',
                'server logs, infrastructure backups and third-party processor records',
            ],
        ];
    }

    /**
     * @param  array<int, int|string>  $ids
     * @param  array<int, string>|null  $columns
     * @return array<int, array<string, mixed>>
     */
    private function relatedRows(string $table, string $foreignKey, array $ids, ?array $columns = null): array
    {
        if ($ids === [] || ! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)->whereIn($foreignKey, $ids)->get($columns ?? ['*'])
            ->map(fn (object $row): array => (array) $row)->all();
    }

    private function isoDateTime(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toIso8601String();
    }

    /**
     * @param  array<int, string>|null  $columns
     * @return array<int, array<string, mixed>>
     */
    private function ownedRows(string $table, string $ownerColumn, int|string $ownerId, ?array $columns = null): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table)->where($ownerColumn, $ownerId);

        return ($columns ? $query->get($columns) : $query->get())
            ->map(fn (object $row): array => (array) $row)->all();
    }
}
