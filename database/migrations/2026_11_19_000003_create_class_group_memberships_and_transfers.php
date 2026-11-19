<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_group_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_group_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('joined_at');
            $table->foreignId('removed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
            $table->index(['class_group_id', 'user_id', 'removed_at'], 'class_membership_active_idx');
        });

        // Preserve access to existing class-linked learning records by backfilling
        // one membership per learner/class pair at their earliest recorded activity.
        $historicalMemberships = [];
        if (Schema::hasTable('test_attempts')) {
            DB::table('test_attempts')
                ->selectRaw('class_group_id, user_id, MIN(COALESCE(completed_at, created_at)) as joined_at')
                ->whereNotNull('class_group_id')->whereNotNull('user_id')
                ->groupBy('class_group_id', 'user_id')->get()
                ->each(function (object $row) use (&$historicalMemberships): void {
                    $key = $row->class_group_id.':'.$row->user_id;
                    $historicalMemberships[$key] = [
                        'class_group_id' => $row->class_group_id, 'user_id' => $row->user_id,
                        'joined_at' => $row->joined_at ?? now(),
                    ];
                });
        }
        if (Schema::hasTable('writing_submissions')) {
            DB::table('writing_submissions')
                ->selectRaw('class_group_id, user_id, MIN(COALESCE(submitted_at, created_at)) as joined_at')
                ->whereNotNull('class_group_id')->whereNotNull('user_id')
                ->groupBy('class_group_id', 'user_id')->get()
                ->each(function (object $row) use (&$historicalMemberships): void {
                    $key = $row->class_group_id.':'.$row->user_id;
                    if (! isset($historicalMemberships[$key]) || $row->joined_at < $historicalMemberships[$key]['joined_at']) {
                        $historicalMemberships[$key] = [
                            'class_group_id' => $row->class_group_id, 'user_id' => $row->user_id,
                            'joined_at' => $row->joined_at ?? now(),
                        ];
                    }
                });
        }
        foreach ($historicalMemberships as $membership) {
            DB::table('class_group_memberships')->insert([
                ...$membership, 'added_by' => null, 'removed_by' => null, 'removed_at' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::create('class_group_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_group_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('new_teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('transferred_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('transferred_at');
            $table->timestamps();
        });

        Schema::table('class_groups', function (Blueprint $table): void {
            $table->dropForeign(['teacher_id']);
            $table->foreign('teacher_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->dropForeign(['class_group_id']);
            $table->foreign('class_group_id')->references('id')->on('class_groups')->restrictOnDelete();
        });
        Schema::table('class_group_writing_resource', function (Blueprint $table): void {
            $table->dropForeign(['class_group_id']);
            $table->foreign('class_group_id')->references('id')->on('class_groups')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_group_writing_resource', function (Blueprint $table): void {
            $table->dropForeign(['class_group_id']);
            $table->foreign('class_group_id')->references('id')->on('class_groups')->cascadeOnDelete();
        });
        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->dropForeign(['class_group_id']);
            $table->foreign('class_group_id')->references('id')->on('class_groups')->nullOnDelete();
        });
        Schema::table('class_groups', function (Blueprint $table): void {
            $table->dropForeign(['teacher_id']);
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::dropIfExists('class_group_transfers');
        Schema::dropIfExists('class_group_memberships');
    }
};
