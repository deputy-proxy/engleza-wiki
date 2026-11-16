<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('skills', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('audiences', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('tags', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('type')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('topics', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('topics')->nullOnDelete();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('resources', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('excerpt')->nullable();
            $t->longText('content')->nullable();
            $t->string('type')->default('article');
            $t->string('status')->default('draft');
            $t->foreignId('level_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('featured')->default(false);
            $t->timestamp('published_at')->nullable();
            $t->string('seo_title')->nullable();
            $t->text('seo_description')->nullable();
            $t->string('canonical_url')->nullable();
            $t->boolean('robots_index')->default(true);
            $t->string('og_title')->nullable();
            $t->text('og_description')->nullable();
            $t->string('og_image')->nullable();
            $t->timestamps();
            $t->index(['status', 'published_at']);
        });
        Schema::create('category_resource', function (Blueprint $t) {
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->primary(['resource_id', 'category_id']);
        });
        Schema::create('resource_tag', function (Blueprint $t) {
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['resource_id', 'tag_id']);
        });
        Schema::create('resource_skill', function (Blueprint $t) {
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $t->primary(['resource_id', 'skill_id']);
        });
        Schema::create('audience_resource', function (Blueprint $t) {
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->foreignId('audience_id')->constrained()->cascadeOnDelete();
            $t->primary(['resource_id', 'audience_id']);
        });
        Schema::create('resource_topic', function (Blueprint $t) {
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $t->primary(['resource_id', 'topic_id']);
        });
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resource_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('filename');
            $t->string('path');
            $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->unsignedBigInteger('download_count')->default(0);
            $t->timestamps();
        });
        Schema::create('saved_resources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        foreach (['saved_resources', 'media', 'resource_topic', 'audience_resource', 'resource_skill', 'resource_tag', 'category_resource', 'resources', 'topics', 'categories', 'tags', 'audiences', 'skills', 'levels'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
