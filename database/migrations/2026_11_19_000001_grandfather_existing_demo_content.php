<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration
{
    /**
     * Existing records predate editorial review and are demo content.
     * The original editorial migration was timestamped 2026-11-18 00:00:04.
     * Only records created before that rollout are grandfathered; records created
     * after rollout remain pending and must pass the new review workflow.
     */
    public function up(): void
    {
        foreach (['resources', 'tests', 'learning_lessons', 'content_translations'] as $table) {
            DB::table($table)
                ->where('created_at', '<', '2026-11-18 00:00:04')
                ->where('editorial_status', 'pending')
                ->update([
                    'editorial_status' => 'grandfathered',
                    'editorial_reviewed_by' => null,
                    'editorial_reviewed_at' => null,
                ]);
        }
    }

    public function down(): void
    {
        foreach (['resources', 'tests', 'learning_lessons', 'content_translations'] as $table) {
            DB::table($table)
                ->where('editorial_status', 'grandfathered')
                ->update(['editorial_status' => 'pending']);
        }
    }
};
