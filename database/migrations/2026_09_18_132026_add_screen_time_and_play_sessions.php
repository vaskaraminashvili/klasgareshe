<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('daily_limit_minutes')->nullable()->after('parent_pin_set_at');
            $table->unsignedSmallInteger('screen_time_extra_minutes')->default(0)->after('daily_limit_minutes');
            $table->date('screen_time_extra_on')->nullable()->after('screen_time_extra_minutes');
            $table->boolean('break_reminders')->default(true)->after('screen_time_extra_on');
            $table->boolean('warn_before_limit')->default(true)->after('break_reminders');
            $table->boolean('bedtime_enabled')->default(false)->after('warn_before_limit');
            $table->char('bedtime_start', 5)->nullable()->after('bedtime_enabled');
            $table->char('bedtime_end', 5)->nullable()->after('bedtime_start');
            $table->json('bedtime_days')->nullable()->after('bedtime_end');
            $table->string('timezone', 64)->default('Asia/Tbilisi')->after('bedtime_days');
        });

        Schema::create('user_play_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('last_heartbeat_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('seconds')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_play_sessions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'daily_limit_minutes',
                'screen_time_extra_minutes',
                'screen_time_extra_on',
                'break_reminders',
                'warn_before_limit',
                'bedtime_enabled',
                'bedtime_start',
                'bedtime_end',
                'bedtime_days',
                'timezone',
            ]);
        });
    }
};
