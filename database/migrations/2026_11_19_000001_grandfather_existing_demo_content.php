<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Records already present when the editorial workflow is deployed are demo
     * content. Preserve their current public behavior without claiming they were
     * reviewed. The model hook marks every record created after this migration
     * as pending, so only pre-existing records are grandfathered here.
     */
    public function up(): void
    {
        foreach (['resources', 'tests', 'learning_lessons', 'content_translations'] as $table) {
            DB::table($table)
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
