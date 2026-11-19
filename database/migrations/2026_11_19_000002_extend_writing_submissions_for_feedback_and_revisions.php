<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('writing_submissions', function (Blueprint $table): void {
            // Preserve learner work if an assignment or class is later removed.
            $table->dropForeign(['writing_resource_id']);
            $table->foreign('writing_resource_id')->references('id')->on('writing_resources')->restrictOnDelete();
            $table->foreignId('class_group_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
        });

        Schema::create('class_group_writing_resource', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('writing_resource_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_group_id', 'writing_resource_id'], 'cgwr_assignment_unique');
        });

        Schema::create('writing_submission_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('writing_submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('content');
            $table->unsignedInteger('word_count')->default(0);
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['writing_submission_id', 'version_number'], 'wsv_submission_version_unique');
        });

        Schema::create('writing_submission_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('writing_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('writing_submission_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->json('rubric_scores');
            $table->text('overall_feedback');
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('next_steps')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();
        });

        // Preserve historic text as version one without implying teacher approval.
        DB::table('writing_submissions')->orderBy('id')->chunk(200, function ($submissions): void {
            foreach ($submissions as $submission) {
                DB::table('writing_submission_versions')->insert([
                    'writing_submission_id' => $submission->id,
                    'version_number' => 1,
                    'content' => $submission->content,
                    'word_count' => $submission->word_count,
                    'status' => $submission->status,
                    'submitted_at' => $submission->submitted_at,
                    'created_at' => $submission->created_at,
                    'updated_at' => $submission->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writing_submission_reviews');
        Schema::dropIfExists('writing_submission_versions');
        Schema::dropIfExists('class_group_writing_resource');
        Schema::table('writing_submissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('class_group_id');
            $table->dropForeign(['writing_resource_id']);
            $table->foreign('writing_resource_id')->references('id')->on('writing_resources')->cascadeOnDelete();
        });
    }
};
