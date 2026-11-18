<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('source_url')->nullable();
            $table->string('license')->nullable();
            $table->text('attribution')->nullable();
            $table->text('permission_reference')->nullable();
        });

        Schema::table('listening_resources', function (Blueprint $table): void {
            $table->string('media_source_url')->nullable();
            $table->string('media_license')->nullable();
            $table->text('media_attribution')->nullable();
            $table->text('media_permission_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listening_resources', function (Blueprint $table): void {
            $table->dropColumn(['media_source_url', 'media_license', 'media_attribution', 'media_permission_reference']);
        });

        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'license', 'attribution', 'permission_reference']);
        });
    }
};
