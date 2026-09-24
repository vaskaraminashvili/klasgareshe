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
        Schema::table('user_stats', function (Blueprint $table) {
            $table->unsignedInteger('coins')->default(0)->after('xp');
        });

        DB::table('user_stats')->update(['coins' => DB::raw('xp')]);

        Schema::create('reward_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('reference', 64);
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'reference']);
            $table->index(['user_id', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_claims');

        Schema::table('user_stats', function (Blueprint $table) {
            $table->dropColumn('coins');
        });
    }
};
