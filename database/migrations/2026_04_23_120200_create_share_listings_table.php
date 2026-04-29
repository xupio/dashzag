<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('miner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('share_holding_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('remaining_quantity');
            $table->decimal('price_per_share', 12, 2);
            $table->decimal('total_price', 14, 2);
            $table->decimal('platform_fee_percent', 5, 2)->default(0);
            $table->decimal('platform_fee_amount', 14, 2)->default(0);
            $table->decimal('seller_net_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamp('listed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_listings');
    }
};
