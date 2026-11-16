<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_resources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->text('instructions')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('resource_cards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('card_resource_id')->constrained()->cascadeOnDelete();
            $t->longText('front');
            $t->longText('back');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('listening_resources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->text('instructions')->nullable();
            $t->string('media_path')->nullable();
            $t->string('media_type')->default('audio'); // audio|video
            $t->string('original_filename')->nullable();
            $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->timestamps();
        });
        Schema::create('reading_resources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->string('title')->nullable();
            $t->text('instructions')->nullable();
            $t->longText('content');
            $t->timestamps();
        });
        Schema::create('writing_resources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->text('prompt');
            $t->text('instructions')->nullable();
            $t->unsignedInteger('minimum_words')->nullable();
            $t->unsignedInteger('maximum_words')->nullable();
            $t->unsignedInteger('recommended_time_minutes')->nullable();
            $t->text('rubric')->nullable();
            $t->longText('example_answer')->nullable();
            $t->timestamps();
        });
        Schema::create('writing_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('writing_resource_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->longText('content');
            $t->unsignedInteger('word_count')->default(0);
            $t->timestamp('submitted_at')->nullable();
            $t->string('status')->default('draft');
            $t->timestamps();
        });

        // Make Question schema flexible: questions can belong to a TestSection OR a ListeningResource OR a ReadingResource.
        Schema::table('questions', function (Blueprint $t) {
            $t->unsignedBigInteger('test_section_id')->nullable()->change();
            $t->foreignId('listening_resource_id')->nullable()->after('test_section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reading_resource_id')->nullable()->after('listening_resource_id')->constrained()->cascadeOnDelete();
        });

        // Learning attempts (for listening/reading exercises) — parallel to TestAttempt but for a Resource.
        Schema::create('learning_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('session_id')->nullable()->index();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->string('status')->default('started');
            $t->float('score')->nullable();
            $t->float('percentage')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
        });
        Schema::create('learning_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('learning_attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $t->text('answer_text')->nullable();
            $t->boolean('is_correct')->nullable();
            $t->float('points_awarded')->nullable();
            $t->timestamp('answered_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_answers');
        Schema::dropIfExists('learning_attempts');
        Schema::table('questions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('listening_resource_id');
            $t->dropConstrainedForeignId('reading_resource_id');
        });
        Schema::dropIfExists('writing_submissions');
        Schema::dropIfExists('writing_resources');
        Schema::dropIfExists('reading_resources');
        Schema::dropIfExists('listening_resources');
        Schema::dropIfExists('resource_cards');
        Schema::dropIfExists('card_resources');
    }
};
