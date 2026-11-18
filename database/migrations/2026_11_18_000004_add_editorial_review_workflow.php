<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = ['resources', 'tests', 'learning_lessons', 'content_translations'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('editorial_status', 20)->default('pending')->index();
                $table->foreignId('editorial_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('editorial_reviewed_at')->nullable();
                $table->text('editorial_notes')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('editorial_reviewed_by');
                $table->dropIndex(['editorial_status']);
                $table->dropColumn(['editorial_status', 'editorial_reviewed_at', 'editorial_notes']);
            });
        }
    }
};
