<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adaptive_practice_sessions', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'completed_at'], 'adaptive_sessions_learner_progress_idx');
        });
    }

    public function down(): void
    {
        Schema::table('adaptive_practice_sessions', function (Blueprint $table): void {
            $table->dropIndex('adaptive_sessions_learner_progress_idx');
        });
    }
};
