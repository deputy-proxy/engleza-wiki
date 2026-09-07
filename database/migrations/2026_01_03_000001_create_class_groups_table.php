<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('class_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('join_code', 12)->unique();
            $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('test_id')->nullable()->constrained('tests')->nullOnDelete();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::table('test_attempts', function (Blueprint $t) {
            $t->foreignId('class_group_id')->nullable()->after('user_id')->constrained('class_groups')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('test_attempts', fn(Blueprint $t) => $t->dropConstrainedForeignId('class_group_id'));
        Schema::dropIfExists('class_groups');
    }
};
