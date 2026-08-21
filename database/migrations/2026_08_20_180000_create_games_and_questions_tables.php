<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title', 128)->nullable();
            $table->string('slug', 32)->unique();
            $table->string('format', 32);
            $table->unsignedTinyInteger('lives')->default(3);
            $table->unsignedTinyInteger('questions_per_round')->default(10);
            $table->unsignedSmallInteger('xp_per_correct')->default(8);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('format', 32);
            $table->string('source', 32)->nullable();
            $table->string('subject', 32);
            $table->string('age_group', 32)->nullable();
            $table->string('locale', 8)->default('ka');
            $table->text('prompt')->nullable();
            $table->text('hint')->nullable();
            $table->json('media')->nullable();
            $table->json('payload');
            $table->json('answer');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['format', 'locale', 'is_active']);
            $table->index('source');
            $table->index('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
        Schema::dropIfExists('games');
    }
};
