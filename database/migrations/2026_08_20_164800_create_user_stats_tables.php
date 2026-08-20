<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_played_on')->nullable();
            $table->string('league', 32)->default('bronze');
            $table->timestamps();
        });

        Schema::create('user_activity_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('played_on');
            $table->unsignedInteger('xp_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'played_on']);
        });

        $now = now();

        foreach (DB::table('users')->pluck('id') as $userId) {
            DB::table('user_stats')->insert([
                'user_id' => $userId,
                'xp' => 0,
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_played_on' => null,
                'league' => 'bronze',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_days');
        Schema::dropIfExists('user_stats');
    }
};
