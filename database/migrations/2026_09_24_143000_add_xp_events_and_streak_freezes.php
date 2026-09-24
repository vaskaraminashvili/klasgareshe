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
        Schema::table('user_stats', function (Blueprint $table) {
            $table->unsignedTinyInteger('streak_freezes')->default(0)->after('longest_streak');
        });

        Schema::table('user_activity_days', function (Blueprint $table) {
            $table->boolean('frozen')->default(false)->after('xp_earned');
        });

        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);
            $table->string('subject', 32)->nullable();
            $table->unsignedInteger('amount');
            $table->string('context', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'source', 'context']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xp_events');

        Schema::table('user_activity_days', function (Blueprint $table) {
            $table->dropColumn('frozen');
        });

        Schema::table('user_stats', function (Blueprint $table) {
            $table->dropColumn('streak_freezes');
        });
    }
};
