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
            $table->unsignedTinyInteger('age')->nullable()->after('nickname');
            $table->string('gender', 16)->nullable()->after('age');
            $table->string('age_group', 32)->nullable()->after('gender');
            $table->json('favourite_subjects')->nullable()->after('age_group');
            $table->string('daily_goal', 32)->nullable()->after('favourite_subjects');
            $table->string('onboarding_step', 32)->nullable()->after('daily_goal');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            $table->json('notification_preferences')->nullable()->after('onboarding_completed_at');
            $table->string('reminder_time', 32)->nullable()->after('notification_preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'age',
                'gender',
                'age_group',
                'favourite_subjects',
                'daily_goal',
                'onboarding_step',
                'onboarding_completed_at',
                'notification_preferences',
                'reminder_time',
            ]);
        });
    }
};
