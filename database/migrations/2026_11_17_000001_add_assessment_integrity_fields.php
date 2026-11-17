<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->json('question_order')->nullable();
            $table->unsignedInteger('total_points')->nullable();
            $table->boolean('passed')->nullable();
        });

        Schema::table('learning_attempts', function (Blueprint $table): void {
            $table->json('question_order')->nullable();
            $table->unsignedInteger('total_points')->nullable();
            $table->json('breakdown')->nullable();
        });

        Schema::table('assessment_results', function (Blueprint $table): void {
            $table->unsignedInteger('total_points')->nullable();
            $table->json('breakdown')->nullable();
        });

    }

    public function down(): void
    {
        Schema::table('assessment_results', function (Blueprint $table): void {
            $table->dropColumn(['total_points', 'breakdown']);
        });
        Schema::table('learning_attempts', function (Blueprint $table): void {
            $table->dropColumn(['question_order', 'total_points', 'breakdown']);
        });
        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->dropColumn(['question_order', 'total_points', 'passed']);
        });
    }
};
