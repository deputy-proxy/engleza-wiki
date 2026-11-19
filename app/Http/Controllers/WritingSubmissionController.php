<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\Resource;
use App\Models\WritingSubmission;
use App\Services\Learning\WritingSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WritingSubmissionController extends Controller
{
    public function store(Request $request, Resource $resource, WritingSubmissionService $service): RedirectResponse
    {
        abort_unless($resource->type === 'writing' && $resource->writingResource, 404);
        abort_unless($resource->status === 'published' && $resource->isEditorialVisible(), 404);
        $data = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'submitted'])],
        ]);
        $submission = $service->saveLearnerVersion(
            $request->user(), $resource->writingResource, $data['content'], $data['status'] === 'submitted',
        );

        return redirect()->route('dashboard.writing.show', $submission)->with('status', 'writing-saved');
    }

    public function storeForClass(Request $request, ClassGroup $group, Resource $resource, WritingSubmissionService $service): RedirectResponse
    {
        abort_unless($group->is_active, 404);
        abort_unless($resource->type === 'writing' && $resource->writingResource, 404);
        abort_unless($resource->status === 'published' && $resource->isEditorialVisible(), 404);
        abort_unless($group->writingResources()->whereKey($resource->writingResource->id)->exists(), 404);
        $data = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'submitted'])],
        ]);
        $submission = $service->saveLearnerVersion(
            $request->user(), $resource->writingResource, $data['content'], $data['status'] === 'submitted', null, $group,
        );

        return redirect()->route('dashboard.writing.show', $submission)->with('status', 'writing-saved');
    }

    public function show(Request $request, WritingSubmission $submission): View
    {
        abort_unless($submission->user_id === $request->user()->id, 404);
        $submission->load(['writingResource.translations', 'writingResource.resource.translations', 'versions', 'reviews.teacher', 'reviews.version']);

        return view('pages.dashboard.writing-show', compact('submission'));
    }

    public function revise(Request $request, WritingSubmission $submission, WritingSubmissionService $service): RedirectResponse
    {
        abort_unless($submission->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'submitted'])],
        ]);
        $submission = $service->saveLearnerVersion(
            $request->user(), $submission->writingResource, $data['content'], $data['status'] === 'submitted', $submission,
        );

        return redirect()->route('dashboard.writing.show', $submission)->with('status', 'writing-saved');
    }

    public function teacherIndex(Request $request, ClassGroup $group): View
    {
        $this->authorizeTeacher($request, $group);
        $submissions = $group->writingSubmissions()->where('status', '!=', 'draft')->with(['user', 'writingResource.resource.translations'])->latest()->paginate(20);

        return view('pages.classes.writing', compact('group', 'submissions'));
    }

    public function teacherShow(Request $request, ClassGroup $group, WritingSubmission $submission): View
    {
        $this->authorizeTeacher($request, $group);
        abort_unless($submission->class_group_id === $group->id && $submission->status !== 'draft', 404);
        $submission->load(['user', 'writingResource.resource.translations', 'versions', 'reviews.teacher', 'reviews.version']);

        return view('pages.classes.writing-review', compact('group', 'submission'));
    }

    public function beginReview(Request $request, ClassGroup $group, WritingSubmission $submission, WritingSubmissionService $service): RedirectResponse
    {
        $this->authorizeTeacher($request, $group);
        abort_unless($submission->class_group_id === $group->id, 404);
        $service->beginReview($submission);

        return back()->with('status', 'writing-review-started');
    }

    public function publishFeedback(Request $request, ClassGroup $group, WritingSubmission $submission, WritingSubmissionService $service): RedirectResponse
    {
        $this->authorizeTeacher($request, $group);
        abort_unless($submission->class_group_id === $group->id, 404);
        $criteria = ['task_achievement', 'organization', 'vocabulary', 'grammar', 'mechanics'];
        $data = $request->validate([
            'rubric_scores' => ['required', 'array:'.implode(',', $criteria)],
            ...collect($criteria)->mapWithKeys(fn (string $criterion): array => ["rubric_scores.{$criterion}" => ['required', 'integer', 'between:1,4']])->all(),
            'overall_feedback' => ['required', 'string', 'max:5000'],
            'strengths' => ['nullable', 'string', 'max:3000'],
            'improvements' => ['nullable', 'string', 'max:3000'],
            'next_steps' => ['nullable', 'string', 'max:3000'],
        ]);
        $service->publishFeedback($submission, $request->user(), $data['rubric_scores'], $data);

        return redirect()->route('classes.writing.show', [$group, $submission])->with('status', 'writing-feedback-published');
    }

    public function requestRevision(Request $request, ClassGroup $group, WritingSubmission $submission, WritingSubmissionService $service): RedirectResponse
    {
        $this->authorizeTeacher($request, $group);
        abort_unless($submission->class_group_id === $group->id, 404);
        $service->requestRevision($submission);

        return back()->with('status', 'writing-revision-requested');
    }

    public function complete(Request $request, ClassGroup $group, WritingSubmission $submission, WritingSubmissionService $service): RedirectResponse
    {
        $this->authorizeTeacher($request, $group);
        abort_unless($submission->class_group_id === $group->id, 404);
        $service->complete($submission);

        return back()->with('status', 'writing-completed');
    }

    private function authorizeTeacher(Request $request, ClassGroup $group): void
    {
        abort_unless($request->user()->isAdmin() || ($request->user()->role === 'teacher' && $group->teacher_id === $request->user()->id), 403);
    }
}
