<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\TestAttempt;
use App\Services\Learning\LearnerProgressService;

class ClassGroupController extends Controller
{
    // Public join / landing page (by code)
    public function show(string $code)
    {
        $group = ClassGroup::where('join_code', strtoupper($code))->where('is_active', true)->firstOrFail();
        $group->load('test.level', 'teacher');

        return view('pages.classes.show', compact('group'));
    }

    // Teacher-only aggregate results. Summary evidence is scoped to this group only.
    public function results(string $code, LearnerProgressService $progress)
    {
        $group = ClassGroup::where('join_code', strtoupper($code))->firstOrFail();
        abort_unless(auth()->check() && ((string) auth()->id() === (string) $group->teacher_id || auth()->user()->role === 'admin'), 403);
        $attempts = TestAttempt::where('class_group_id', $group->id)->with('test', 'user')->latest()->get();
        $completed = $attempts->where('status', 'completed');
        $avg = $completed->count() ? round($completed->avg('percentage'), 1) : 0;
        $levels = $completed->groupBy('level_result')->map->count();
        $learnerProgress = $progress->classGroupSummary($group);

        return view('pages.classes.results', compact('group', 'attempts', 'completed', 'avg', 'levels', 'learnerProgress'));
    }
}
