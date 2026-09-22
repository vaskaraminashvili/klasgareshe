<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_parent_email')->nullable()->after('email_verified_at');
            $table->string('pending_parent_email_token', 64)->nullable()->unique()->after('pending_parent_email');
            $table->timestamp('pending_parent_email_sent_at')->nullable()->after('pending_parent_email_token');
            $table->timestamp('deletion_requested_at')->nullable()->after('remember_token');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pending_parent_email_token']);
            $table->dropColumn([
                'pending_parent_email',
                'pending_parent_email_token',
                'pending_parent_email_sent_at',
                'deletion_requested_at',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
