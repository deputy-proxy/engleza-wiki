<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_answers', function (Blueprint $table): void {
            $table->json('answer_payload')->nullable()->after('answer_text');
        });

        Schema::table('learning_answers', function (Blueprint $table): void {
            $table->json('answer_payload')->nullable()->after('answer_text');
        });
    }

    public function down(): void
    {
        Schema::table('learning_answers', function (Blueprint $table): void {
            $table->dropColumn('answer_payload');
        });

        Schema::table('test_answers', function (Blueprint $table): void {
            $table->dropColumn('answer_payload');
        });
    }
};
