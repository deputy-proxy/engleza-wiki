<?php

namespace App\Services\Learning;

use App\Models\ClassGroup;
use App\Models\User;
use App\Models\WritingResource;
use App\Models\WritingSubmission;
use App\Models\WritingSubmissionReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WritingSubmissionService
{
    /** @var array<string, string> */
    public const RUBRIC = [
        'task_achievement' => 'Task achievement',
        'organization' => 'Organization and coherence',
        'vocabulary' => 'Vocabulary and range',
        'grammar' => 'Grammar and accuracy',
        'mechanics' => 'Spelling and punctuation',
    ];

    public function saveLearnerVersion(
        User $learner,
        WritingResource $writingResource,
        string $content,
        bool $submit,
        ?WritingSubmission $submission = null,
        ?ClassGroup $classGroup = null,
    ): WritingSubmission {
        return DB::transaction(function () use ($learner, $writingResource, $content, $submit, $submission, $classGroup): WritingSubmission {
            if ($submission) {
                $submission = WritingSubmission::query()->lockForUpdate()->findOrFail($submission->id);
                if ($submission->user_id !== $learner->id || $submission->writing_resource_id !== $writingResource->id) {
                    abort(404);
                }
                if (! in_array($submission->status, ['draft', 'revision_requested'], true)) {
                    throw ValidationException::withMessages(['content' => 'This submission is locked. A teacher must request a revision before it can be changed.']);
                }
                if ($classGroup && $submission->class_group_id !== $classGroup->id) {
                    abort(404);
                }
            } else {
                $submission = WritingSubmission::create([
                    'writing_resource_id' => $writingResource->id,
                    'user_id' => $learner->id,
                    'class_group_id' => $classGroup?->id,
                    'content' => $content,
                    'word_count' => $this->wordCount($content),
                    'status' => 'draft',
                ]);
            }

            $wordCount = $this->wordCount($content);
            $status = match (true) {
                $submit && $submission->status === 'revision_requested' => 'resubmitted',
                $submit => 'submitted',
                default => $submission->status,
            };
            $submittedAt = $submit ? now() : $submission->submitted_at;
            $nextVersion = ((int) $submission->versions()->max('version_number')) + 1;

            $submission->forceFill([
                'content' => $content,
                'word_count' => $wordCount,
                'status' => $status,
                'submitted_at' => $submittedAt,
            ])->save();

            $submission->versions()->create([
                'version_number' => $nextVersion,
                'content' => $content,
                'word_count' => $wordCount,
                'status' => $status,
                'submitted_at' => $submittedAt,
            ]);

            return $submission->refresh()->load('versions');
        });
    }

    public function beginReview(WritingSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $locked = WritingSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if (! in_array($locked->status, ['submitted', 'resubmitted'], true)) {
                throw ValidationException::withMessages(['status' => 'Only submitted or resubmitted work can enter review.']);
            }

            $locked->update(['status' => 'under_review']);
        });
    }

    /** @param array<string, int> $scores */
    /**
     * @param  array<string, int>  $scores
     * @param  array{overall_feedback: string, strengths?: string|null, improvements?: string|null, next_steps?: string|null}  $feedback
     */
    public function publishFeedback(WritingSubmission $submission, User $teacher, array $scores, array $feedback): WritingSubmissionReview
    {
        return DB::transaction(function () use ($submission, $teacher, $scores, $feedback): WritingSubmissionReview {
            $locked = WritingSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->status !== 'under_review') {
                throw ValidationException::withMessages(['status' => 'Start the review before publishing feedback.']);
            }
            $version = $locked->versions()->latest('version_number')->first();
            if (! $version) {
                throw ValidationException::withMessages(['content' => 'This submission has no version available to review.']);
            }

            $review = WritingSubmissionReview::create([
                'writing_submission_id' => $locked->id,
                'writing_submission_version_id' => $version->id,
                'teacher_id' => $teacher->id,
                'rubric_scores' => $scores,
                'overall_feedback' => $feedback['overall_feedback'],
                'strengths' => $feedback['strengths'] ?? null,
                'improvements' => $feedback['improvements'] ?? null,
                'next_steps' => $feedback['next_steps'] ?? null,
                'published_at' => now(),
            ]);

            $locked->update(['status' => 'feedback_published']);

            return $review;
        });
    }

    public function requestRevision(WritingSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $locked = WritingSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->status !== 'feedback_published') {
                throw ValidationException::withMessages(['status' => 'Revision can only be requested after feedback is published.']);
            }

            $locked->update(['status' => 'revision_requested']);
        });
    }

    public function complete(WritingSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $locked = WritingSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->status !== 'feedback_published') {
                throw ValidationException::withMessages(['status' => 'Only work with published feedback can be marked complete.']);
            }

            $locked->update(['status' => 'completed']);
        });
    }

    private function wordCount(string $content): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags($content)), -1, PREG_SPLIT_NO_EMPTY);

        return count($words ?: []);
    }
}
