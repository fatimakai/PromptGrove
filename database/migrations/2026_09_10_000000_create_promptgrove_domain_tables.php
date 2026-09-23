<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('prompt_text');
            $table->string('target_model', 100);
            $table->longText('example_input')->nullable();
            $table->longText('example_output')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->timestamps();

            $table->index(['visibility', 'created_at']);
            $table->index(['user_id', 'visibility']);
            $table->index('target_model');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('prompt_tag', function (Blueprint $table) {
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['prompt_id', 'tag_id']);
        });

        Schema::create('upvotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'prompt_id']);
            $table->index('prompt_id');
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'prompt_id']);
            $table->index('prompt_id');
        });

        Schema::create('prompt_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->json('analysis')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();
            $table->index(['prompt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_analyses');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('upvotes');
        Schema::dropIfExists('prompt_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('prompts');
    }
};
