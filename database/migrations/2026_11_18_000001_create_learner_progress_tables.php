<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_vocabulary_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_type', 32);
            $table->char('item_key', 64);
            $table->text('prompt');
            $table->text('answer');
            $table->unsignedInteger('interval_seconds')->default(0);
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedSmallInteger('repetitions')->default(0);
            $table->unsignedSmallInteger('lapses')->default(0);
            $table->string('last_grade', 16)->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('due_at')->index();
            $table->timestamps();
            $table->unique(['user_id', 'item_key']);
            $table->index(['user_id', 'due_at', 'id']);
        });

        Schema::create('vocabulary_review_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learner_vocabulary_item_id')->constrained('learner_vocabulary_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('grade', 16);
            $table->unsignedInteger('previous_interval_seconds')->default(0);
            $table->unsignedInteger('next_interval_seconds');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->index(['user_id', 'reviewed_at']);
        });

        Schema::create('adaptive_practice_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('question_order');
            $table->string('status')->default('started');
            $table->string('difficulty_min', 16)->default('easy');
            $table->string('difficulty_max', 16)->default('medium');
            $table->unsignedInteger('earned_points')->nullable();
            $table->unsignedInteger('total_points')->nullable();
            $table->float('percentage')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'started_at']);
        });

        Schema::create('adaptive_practice_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('adaptive_practice_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('answer_payload')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->float('points_awarded')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['adaptive_practice_session_id', 'question_id'], 'adaptive_session_question_unique');
        });

        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'completed_at'], 'test_attempts_learner_progress_idx');
        });

        Schema::table('learning_attempts', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'completed_at'], 'learning_attempts_learner_progress_idx');
        });
    }

    public function down(): void
    {
        Schema::table('learning_attempts', fn (Blueprint $table) => $table->dropForeign(['user_id']));
        if (DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'learning_attempts')->where('index_name', 'learning_attempts_learner_progress_idx')->exists()) {
            Schema::table('learning_attempts', fn (Blueprint $table) => $table->dropIndex('learning_attempts_learner_progress_idx'));
        }
        Schema::table('learning_attempts', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->nullOnDelete());

        Schema::table('test_attempts', fn (Blueprint $table) => $table->dropForeign(['user_id']));
        if (DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'test_attempts')->where('index_name', 'test_attempts_learner_progress_idx')->exists()) {
            Schema::table('test_attempts', fn (Blueprint $table) => $table->dropIndex('test_attempts_learner_progress_idx'));
        }
        Schema::table('test_attempts', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->nullOnDelete());

        Schema::dropIfExists('adaptive_practice_answers');
        Schema::dropIfExists('adaptive_practice_sessions');
        Schema::dropIfExists('vocabulary_review_events');
        Schema::dropIfExists('learner_vocabulary_items');
    }
};
