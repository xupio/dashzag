<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('miner_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('locked_quantity')->default(0);
            $table->decimal('avg_buy_price', 12, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamp('last_acquired_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'miner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_holdings');
    }
};
