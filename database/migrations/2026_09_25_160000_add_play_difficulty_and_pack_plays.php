<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('play_difficulty', 16)->default('medium')->after('grade');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('difficulty', 16)->default('medium')->after('grade');
            $table->index('difficulty');
        });

        Schema::create('pack_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('week_plan_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('game_slug', 32);
            $table->unsignedTinyInteger('correct_count')->default(0);
            $table->date('played_on');
            $table->timestamps();

            $table->index(['user_id', 'week_plan_item_id', 'played_on'], 'pack_plays_user_item_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_plays');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['difficulty']);
            $table->dropColumn('difficulty');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('play_difficulty');
        });
    }
};
