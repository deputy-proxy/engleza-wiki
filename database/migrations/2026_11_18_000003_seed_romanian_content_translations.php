<?php

use Database\Seeders\RomanianTranslationsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RomanianTranslationsSeeder::class)->run();
    }

    public function down(): void
    {
        // Translation records are data; keep them intact when rolling back this seed step.
    }
};
