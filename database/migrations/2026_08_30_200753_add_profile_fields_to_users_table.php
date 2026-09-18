<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 16)->nullable()->after('nickname');
            // Product default: a new kid is visible on the global leaderboard until a parent opts out.
            $table->boolean('show_on_leaderboard')->default(true)->after('reminder_time');
            $table->boolean('allow_friend_requests')->default(true)->after('show_on_leaderboard');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'show_on_leaderboard', 'allow_friend_requests']);
        });
    }
};
