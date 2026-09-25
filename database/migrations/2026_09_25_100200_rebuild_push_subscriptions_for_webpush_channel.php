<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NotificationChannels\WebPush\PushSubscription;

return new class extends Migration
{
    /**
     * Rebuild the T16 table to the laravel-notification-channels/webpush schema.
     */
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions') || ! Schema::hasColumn('push_subscriptions', 'endpoint_hash')) {
            return;
        }

        Schema::drop('push_subscriptions');

        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', PushSubscription::ENDPOINT_MAX_LENGTH)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Irreversible rebuild of a table that never shipped.
    }
};
