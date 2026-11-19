<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\TestAttempt;
use App\Services\Learning\ClassGroupService;
use App\Services\Learning\LearnerProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassGroupController extends Controller
{
    // Public join / landing page (by code)
    public function show(string $code)
    {
        $group = ClassGroup::where('join_code', strtoupper($code))->where('is_active', true)->firstOrFail();
        $group->load(['test.translations', 'test.level.translations', 'teacher:id,name', 'writingResources.resource.translations', 'writingResources.resource.level.translations']);
        $isMember = auth()->check() && $group->activeMemberships()->where('user_id', auth()->id())->exists();
        if ($group->test === null || $group->test->status !== 'published' || $group->test->editorial_status !== 'approved' || ! $group->test->is_public) {
            $group->setRelation('test', null);
        }
        $group->setRelation('writingResources', $group->writingResources->filter(
            fn ($writing) => $writing->resource->status === 'published' && $writing->resource->editorial_status === 'approved'
        )->values());

        return view('pages.classes.show', compact('group', 'isMember'));
    }

    // Teacher-only aggregate results. Summary evidence is scoped to this group only.
    public function results(string $code, LearnerProgressService $progress)
    {
        $group = ClassGroup::where('join_code', strtoupper($code))->firstOrFail();
        abort_unless(auth()->check() && (auth()->user()->isAdmin() || (auth()->user()->role === 'teacher' && auth()->id() === $group->teacher_id)), 403);
        $attempts = TestAttempt::where('class_group_id', $group->id)->whereNotNull('user_id')->whereHas('user.classGroupMemberships', fn ($q) => $q->where('class_group_id', $group->id)->whereNull('removed_at'))->with(['test.translations', 'test.level.translations', 'user:id,name'])->latest()->get();
        $completed = $attempts->where('status', 'completed');
        $avg = $completed->count() ? round($completed->avg('percentage'), 1) : 0;
        $levels = $completed->groupBy('level_result')->map->count();
        $learnerProgress = $progress->classGroupSummary($group);
        $writingSubmissionCount = $group->writingSubmissions()->whereHas('user.classGroupMemberships', fn ($q) => $q->where('class_group_id', $group->id)->whereNull('removed_at'))->count();

        return view('pages.classes.results', compact('group', 'attempts', 'completed', 'avg', 'levels', 'learnerProgress', 'writingSubmissionCount'));
    }

    public function join(Request $request, string $code, ClassGroupService $service): RedirectResponse
    {
        $group = ClassGroup::query()->where('join_code', strtoupper($code))->where('is_active', true)->firstOrFail();
        abort_unless($request->user()->role === 'learner', 403);
        $service->join($request->user(), $group);

        return redirect()->route('classes.show', $group->join_code)->with('status', 'class-joined');
    }
}
