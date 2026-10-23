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
        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category', 32);
            $table->string('emoji', 16);
            $table->string('tile', 32);
            $table->unsignedInteger('price');
            $table->unsignedInteger('list_price')->nullable();
            $table->string('tag', 16)->nullable();
            $table->unsignedTinyInteger('sale_percent')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('user_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_item_id')->constrained()->cascadeOnDelete();
            $table->string('purchase_key', 64)->default('');
            $table->unsignedInteger('price_paid')->default(0);
            $table->boolean('equipped')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'shop_item_id', 'purchase_key']);
            $table->index(['user_id', 'equipped']);
        });

        Schema::create('user_boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('multiplier');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['user_id', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_boosts');
        Schema::dropIfExists('user_inventory');
        Schema::dropIfExists('shop_items');
    }
};
