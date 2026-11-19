<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\ClassGroupMembership;
use App\Models\Test;
use App\Models\User;
use App\Models\WritingResource;
use App\Services\Learning\ClassGroupService;
use App\Services\Learning\LearnerProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeachingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'teacher', 403);
        $query = ClassGroup::query()->with(['teacher:id,name', 'test.translations'])
            ->withCount(['activeMemberships as members_count', 'activeAttempts as attempts_count'])
            ->latest();
        if (! $request->user()->isAdmin()) {
            $query->where('teacher_id', $request->user()->id);
        }
        $groups = $query->paginate(15);

        return view('pages.teaching.index', compact('groups'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'teacher', 403);

        return view('pages.teaching.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'teacher', 403);
        $rules = ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000']];
        if ($request->user()->isAdmin()) {
            $rules['teacher_id'] = ['required', 'integer', Rule::exists('users', 'id')->where('role', 'teacher')];
        }
        $data = $request->validate($rules);
        $group = ClassGroup::query()->create([
            'name' => $data['name'], 'description' => $data['description'] ?? null,
            'teacher_id' => $request->user()->isAdmin() ? $data['teacher_id'] : $request->user()->id,
        ]);

        return redirect()->route('teaching.show', $group)->with('status', 'class-created');
    }

    public function show(Request $request, ClassGroup $group, LearnerProgressService $progress): View
    {
        $this->authorizeGroup($request, $group);
        $group->load(['teacher:id,name', 'test.translations', 'writingResources.resource.translations', 'activeMemberships.user:id,name']);
        $progressByUser = $progress->classGroupSummary($group);
        $availableTests = Test::query()->published()->where('editorial_status', 'approved')->where('is_public', true)->with('translations')->get();
        $availableWritingResources = WritingResource::query()->whereHas('resource', fn ($q) => $q->where('type', 'writing')->where('status', 'published')->where('editorial_status', 'approved'))->with('resource.translations')->get();
        $teachers = User::query()->where('role', 'teacher')->whereKeyNot($group->teacher_id)->orderBy('name')->get(['id', 'name']);
        $recentAttempts = $group->activeAttempts()->with(['user:id,name', 'test.translations'])->latest()->limit(5)->get();
        $recentWritingSubmissions = $group->writingSubmissions()->where('status', '!=', 'draft')
            ->whereHas('user.classGroupMemberships', fn ($q) => $q->where('class_group_id', $group->id)->whereNull('removed_at'))
            ->with(['user:id,name', 'writingResource.resource.translations'])->latest()->limit(5)->get();

        return view('pages.teaching.show', compact('group', 'progressByUser', 'availableTests', 'availableWritingResources', 'teachers', 'recentAttempts', 'recentWritingSubmissions'));
    }

    public function update(Request $request, ClassGroup $group): RedirectResponse
    {
        $this->authorizeGroup($request, $group);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000'],
            'test_id' => ['nullable', 'integer', Rule::exists('tests', 'id')->where(fn ($q) => $q->where('status', 'published')->where('editorial_status', 'approved')->where('is_public', true))],
            'writing_resource_ids' => ['nullable', 'array'], 'writing_resource_ids.*' => ['integer', 'distinct', 'exists:writing_resources,id'],
        ]);
        $writingIds = $data['writing_resource_ids'] ?? [];
        $eligibleWritingIds = WritingResource::query()->whereIn('id', $writingIds)
            ->whereHas('resource', fn ($q) => $q->where('type', 'writing')->where('status', 'published')->where('editorial_status', 'approved'))
            ->pluck('id')->all();
        if (count($eligibleWritingIds) !== count($writingIds)) {
            throw ValidationException::withMessages(['writing_resource_ids' => __('Only published, approved writing tasks can be assigned.')]);
        }
        DB::transaction(function () use ($request, $group, $data, $eligibleWritingIds): void {
            $locked = ClassGroup::query()->lockForUpdate()->findOrFail($group->id);
            abort_unless($request->user()->isAdmin() || $locked->teacher_id === $request->user()->id, 403);
            $locked->update(['name' => $data['name'], 'description' => $data['description'] ?? null, 'test_id' => $data['test_id'] ?? null]);
            $locked->writingResources()->sync($eligibleWritingIds);
        });

        return back()->with('status', 'class-updated');
    }

    public function addMember(Request $request, ClassGroup $group, ClassGroupService $service): RedirectResponse
    {
        $this->authorizeGroup($request, $group);
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $learner = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))])->where('role', 'learner')->first();
        if ($learner === null) {
            return back()->withErrors(['email' => __('No learner account was found for this email address.')]);
        }
        $service->join($learner, $group, $request->user());

        return back()->with('status', 'class-member-added');
    }

    public function removeMember(Request $request, ClassGroup $group, ClassGroupMembership $membership, ClassGroupService $service): RedirectResponse
    {
        $this->authorizeGroup($request, $group);
        $service->remove($group, $membership, $request->user());

        return back()->with('status', 'class-member-removed');
    }

    public function transfer(Request $request, ClassGroup $group, ClassGroupService $service): RedirectResponse
    {
        $this->authorizeGroup($request, $group);
        $data = $request->validate(['teacher_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'teacher')]]);
        $service->transfer($group, User::query()->whereKey($data['teacher_id'])->firstOrFail(), $request->user());

        return redirect()->route('teaching.index')->with('status', 'class-transferred');
    }

    public function toggleActive(Request $request, ClassGroup $group): RedirectResponse
    {
        $this->authorizeGroup($request, $group);
        $active = DB::transaction(function () use ($request, $group): bool {
            $locked = ClassGroup::query()->lockForUpdate()->findOrFail($group->id);
            abort_unless($request->user()->isAdmin() || $locked->teacher_id === $request->user()->id, 403);
            $locked->update(['is_active' => ! $locked->is_active]);

            return $locked->is_active;
        });

        return back()->with('status', $active ? 'class-reopened' : 'class-archived');
    }

    private function authorizeGroup(Request $request, ClassGroup $group): void
    {
        abort_unless($request->user()->isAdmin() || ($request->user()->role === 'teacher' && $group->teacher_id === $request->user()->id), 403);
    }
}
