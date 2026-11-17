<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('level_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at', 'sort_order']);
        });

        Schema::create('learning_path_skill', function (Blueprint $table): void {
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['learning_path_id', 'skill_id']);
        });

        Schema::create('learning_path_topic', function (Blueprint $table): void {
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->primary(['learning_path_id', 'topic_id']);
        });

        Schema::create('learning_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->unique(['learning_path_id', 'sort_order']);
            $table->index(['learning_path_id', 'is_published', 'sort_order']);
        });

        Schema::create('learning_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('objectives')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['learning_unit_id', 'sort_order']);
            $table->index(['learning_unit_id', 'status', 'published_at', 'sort_order'], 'learning_lessons_status_pub_sort_idx');
        });

        Schema::create('learning_lesson_skill', function (Blueprint $table): void {
            $table->foreignId('learning_lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['learning_lesson_id', 'skill_id']);
        });

        Schema::create('learning_lesson_topic', function (Blueprint $table): void {
            $table->foreignId('learning_lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->primary(['learning_lesson_id', 'topic_id']);
        });

        Schema::create('lesson_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_lesson_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('resource_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->index(['learning_lesson_id', 'sort_order']);
        });

        Schema::create('learning_path_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['learning_path_id', 'user_id']);
            $table->index(['user_id', 'activated_at']);
        });

        Schema::create('lesson_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['learning_lesson_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('lesson_activity_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('completed');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['lesson_activity_id', 'user_id']);
            $table->index(['user_id', 'lesson_activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_activity_progress');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('learning_path_enrollments');
        Schema::dropIfExists('lesson_activities');
        Schema::dropIfExists('learning_lesson_topic');
        Schema::dropIfExists('learning_lesson_skill');
        Schema::dropIfExists('learning_lessons');
        Schema::dropIfExists('learning_units');
        Schema::dropIfExists('learning_path_topic');
        Schema::dropIfExists('learning_path_skill');
        Schema::dropIfExists('learning_paths');
    }
};
