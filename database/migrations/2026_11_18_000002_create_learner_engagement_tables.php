<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_engagement_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('daily_goal_xp')->default(20);
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
        });

        Schema::create('learner_daily_engagements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedSmallInteger('goal_xp');
            $table->timestamp('goal_completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'activity_date']);
            $table->index(['user_id', 'goal_completed_at', 'activity_date'], 'learner_daily_user_goal_date_idx');
        });

        Schema::create('experience_point_awards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->unsignedSmallInteger('points');
            $table->date('activity_date');
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'activity_date', 'source_type']);
        });

        Schema::create('learner_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('achievement_key', 64);
            $table->string('title');
            $table->string('description');
            $table->json('evidence')->nullable();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->unique(['user_id', 'achievement_key']);
            $table->index(['user_id', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_achievements');
        Schema::dropIfExists('experience_point_awards');
        Schema::dropIfExists('learner_daily_engagements');
        Schema::dropIfExists('learner_engagement_settings');
    }
};
