<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Class 4 is the only grade now; the trace game is gone. Reseed with WeekPlanSeeder. */
    public function up(): void
    {
        DB::table('users')->whereNotNull('grade')->update(['grade' => 4]);
        DB::table('week_plan_items')->where('grade', '!=', 4)->delete();
        DB::table('questions')->where('source', 'trace-letter')->delete();
        DB::table('games')->where('slug', 'trace-letter')->delete();
    }

    public function down(): void {}
};
