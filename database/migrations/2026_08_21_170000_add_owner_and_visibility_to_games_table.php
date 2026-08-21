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
        Schema::table('games', function (Blueprint $table) {
            $table->dropUnique(['slug']);

            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('visibility', 16)
                ->default('public')
                ->after('is_active');

            $table->index('slug');
            $table->index(['visibility', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['visibility', 'is_active']);
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropIndex(['slug']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('visibility');
            $table->unique('slug');
        });
    }
};
