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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('grade')->nullable()->after('age_group');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->unique()->after('id');
            $table->unsignedTinyInteger('grade')->nullable()->after('age_group');
        });

        Schema::create('week_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('grade');
            $table->unsignedTinyInteger('week_number')->default(1);
            $table->unsignedTinyInteger('weekday');
            $table->string('subject', 32);
            $table->unsignedTinyInteger('level');
            $table->string('title', 128);
            $table->string('game_slug', 32);
            $table->unsignedTinyInteger('questions_per_round')->default(5);
            $table->timestamps();

            $table->unique(['grade', 'week_number', 'weekday', 'subject'], 'week_plan_items_pack_unique');
            $table->index(['grade', 'week_number']);
        });

        Schema::create('week_plan_item_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('week_plan_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['week_plan_item_id', 'question_id']);
        });

        Schema::create('user_plan_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('week_plan_item_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('completed');
            $table->unsignedTinyInteger('correct_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_plan_item_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plan_progress');
        Schema::dropIfExists('week_plan_item_question');
        Schema::dropIfExists('week_plan_items');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'grade']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
