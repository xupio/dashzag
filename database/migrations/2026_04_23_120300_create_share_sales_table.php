<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('share_listings')->cascadeOnDelete();
            $table->foreignId('miner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('price_per_share', 12, 2);
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('platform_fee_percent', 5, 2)->default(0);
            $table->decimal('platform_fee_amount', 14, 2)->default(0);
            $table->decimal('seller_net_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_sales');
    }
};
