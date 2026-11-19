<?php

namespace App\Services\Learning;

use App\Models\ClassGroup;
use App\Models\ClassGroupMembership;
use App\Models\ClassGroupTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassGroupService
{
    public function join(User $learner, ClassGroup $group, ?User $addedBy = null): ClassGroupMembership
    {
        abort_unless($learner->role === 'learner', 403);

        return DB::transaction(function () use ($learner, $group, $addedBy): ClassGroupMembership {
            $locked = ClassGroup::query()->lockForUpdate()->findOrFail($group->id);
            abort_unless($locked->is_active, 404);
            if ($addedBy !== null) {
                abort_unless($addedBy->isAdmin() || ($addedBy->role === 'teacher' && $locked->teacher_id === $addedBy->id), 403);
            }
            $existing = ClassGroupMembership::query()->where('class_group_id', $locked->id)->where('user_id', $learner->id)->whereNull('removed_at')->first();
            if ($existing !== null) {
                return $existing;
            }

            return ClassGroupMembership::query()->create([
                'class_group_id' => $locked->id, 'user_id' => $learner->id,
                'added_by' => $addedBy?->id, 'joined_at' => now(),
            ]);
        });
    }

    public function remove(ClassGroup $group, ClassGroupMembership $membership, User $actor): void
    {
        DB::transaction(function () use ($group, $membership, $actor): void {
            $lockedGroup = ClassGroup::query()->lockForUpdate()->findOrFail($group->id);
            abort_unless($actor->isAdmin() || ($actor->role === 'teacher' && $lockedGroup->teacher_id === $actor->id), 403);
            $locked = ClassGroupMembership::query()->lockForUpdate()->findOrFail($membership->id);
            abort_unless($locked->class_group_id === $lockedGroup->id, 404);
            if ($locked->removed_at === null) {
                $locked->update(['removed_at' => now(), 'removed_by' => $actor->id]);
            }
        });
    }

    public function transfer(ClassGroup $group, User $newTeacher, User $actor): void
    {
        abort_unless($newTeacher->role === 'teacher', 422);
        DB::transaction(function () use ($group, $newTeacher, $actor): void {
            $locked = ClassGroup::query()->lockForUpdate()->findOrFail($group->id);
            abort_unless($actor->isAdmin() || ($actor->role === 'teacher' && $locked->teacher_id === $actor->id), 403);
            if ($locked->teacher_id === $newTeacher->id) {
                throw ValidationException::withMessages(['teacher_id' => 'This teacher already owns the class.']);
            }
            ClassGroupTransfer::query()->create([
                'class_group_id' => $locked->id, 'previous_teacher_id' => $locked->teacher_id,
                'new_teacher_id' => $newTeacher->id, 'transferred_by' => $actor->id, 'transferred_at' => now(),
            ]);
            $locked->update(['teacher_id' => $newTeacher->id]);
        });
    }
}
