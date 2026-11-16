<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->longText('instructions')->nullable();
            $t->foreignId('level_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('duration_minutes')->nullable();
            $t->unsignedInteger('passing_score')->nullable();
            $t->string('access_type')->default('free');
            $t->string('status')->default('draft');
            $t->boolean('is_public')->default(true);
            $t->boolean('shuffle_questions')->default(false);
            $t->boolean('show_correct_answers')->default(true);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });
        Schema::create('test_sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->text('instructions')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_section_id')->constrained()->cascadeOnDelete();
            $t->string('type')->default('single_choice');
            $t->text('question');
            $t->text('explanation')->nullable();
            $t->unsignedInteger('points')->default(1);
            $t->string('difficulty')->default('medium');
            $t->integer('sort_order')->default(0);
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('question_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('label')->nullable();
            $t->text('value');
            $t->boolean('is_correct')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('question_topic', function (Blueprint $t) {
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $t->primary(['question_id', 'topic_id']);
        });
        Schema::create('test_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('session_id')->nullable()->index();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->string('status')->default('started');
            $t->float('score')->nullable();
            $t->float('percentage')->nullable();
            $t->string('level_result')->nullable();
            $t->unsignedBigInteger('time_spent_seconds')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
        });
        Schema::create('test_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $t->text('answer_text')->nullable();
            $t->boolean('is_correct')->nullable();
            $t->float('points_awarded')->nullable();
            $t->timestamp('answered_at')->nullable();
            $t->timestamps();
        });
        Schema::create('assessment_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $t->float('overall_score')->default(0);
            $t->float('overall_percentage')->default(0);
            $t->string('level')->nullable();
            $t->float('grammar_score')->nullable();
            $t->float('vocabulary_score')->nullable();
            $t->float('reading_score')->nullable();
            $t->float('writing_score')->nullable();
            $t->float('listening_score')->nullable();
            $t->float('speaking_score')->nullable();
            $t->longText('recommendation')->nullable();
            $t->timestamps();
        });
        Schema::create('test_attempt_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $t->json('metadata')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['test_attempt_events', 'assessment_results', 'test_answers', 'test_attempts', 'question_topic', 'question_options', 'questions', 'test_sections', 'tests'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
