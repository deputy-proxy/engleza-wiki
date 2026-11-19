<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speaking_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->string('target_skill', 120);
            $table->unsignedSmallInteger('preparation_time_seconds')->default(30);
            $table->unsignedSmallInteger('response_time_seconds')->default(90);
            $table->text('instructions');
            $table->text('response_expectation');
            $table->text('success_criteria');
            $table->text('reflection_prompt');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speaking_resources');
    }
};
