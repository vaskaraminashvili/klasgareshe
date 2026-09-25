<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->index();
            $table->string('avatar_frame', 20)->nullable();
        });

        Schema::table('user_stats', function (Blueprint $table) {
            $table->json('champion_tiers')->nullable();
        });

        Schema::create('league_season_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_week_id')->constrained()->cascadeOnDelete();
            $table->string('tier', 20);
            $table->unsignedInteger('finish_rank');
            $table->string('outcome', 20);
            $table->unsignedInteger('stay_bonus_xp')->default(0);
            $table->unsignedInteger('prize_xp')->nullable();
            $table->timestamp('prize_claimed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'league_week_id']);
        });

        if (Schema::hasTable('badges')) {
            DB::table('badges')->where('slug', 'social-star')->update([
                'rule' => 'friends_count',
                'rule_params' => json_encode(['count' => 5]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('league_season_payouts');

        Schema::table('user_stats', function (Blueprint $table) {
            $table->dropColumn('champion_tiers');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['country', 'avatar_frame']);
        });
    }
};
