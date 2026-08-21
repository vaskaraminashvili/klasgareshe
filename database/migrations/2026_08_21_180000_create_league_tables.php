<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_weeks', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 16)->default('open');
            $table->timestamps();

            $table->unique('starts_on');
            $table->index('status');
        });

        Schema::create('league_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_week_id')->constrained()->cascadeOnDelete();
            $table->string('tier', 32);
            $table->unsignedTinyInteger('capacity')->default(12);
            $table->timestamps();

            $table->index(['league_week_id', 'tier']);
        });

        Schema::create('league_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('week_xp')->default(0);
            $table->unsignedSmallInteger('finish_rank')->nullable();
            $table->string('outcome', 16)->nullable();
            $table->timestamps();

            $table->unique(['league_group_id', 'user_id']);
            $table->index(['league_group_id', 'week_xp']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_group_members');
        Schema::dropIfExists('league_groups');
        Schema::dropIfExists('league_weeks');
    }
};
