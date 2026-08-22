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
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('rarity', 16);
            $table->string('category', 32);
            $table->string('emoji', 16);
            $table->string('medal', 16)->nullable();
            $table->unsignedSmallInteger('xp_bonus')->default(100);
            $table->string('rule', 64);
            $table->json('rule_params')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'badge_id']);
            $table->index(['user_id', 'unlocked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
