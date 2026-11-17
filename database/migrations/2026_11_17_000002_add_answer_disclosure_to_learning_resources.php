<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listening_resources', function (Blueprint $table): void {
            $table->boolean('show_correct_answers')->default(true);
        });
        Schema::table('reading_resources', function (Blueprint $table): void {
            $table->boolean('show_correct_answers')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('listening_resources', function (Blueprint $table): void {
            $table->dropColumn('show_correct_answers');
        });
        Schema::table('reading_resources', function (Blueprint $table): void {
            $table->dropColumn('show_correct_answers');
        });
    }
};
