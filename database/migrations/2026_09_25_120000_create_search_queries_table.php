<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('query', 80);
            $table->string('normalized', 80);
            $table->unsignedInteger('hits')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'normalized']);
            $table->index('normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
