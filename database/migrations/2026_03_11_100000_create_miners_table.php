<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('miners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('total_shares')->default(1000);
            $table->decimal('share_price', 10, 2);
            $table->decimal('daily_output_usd', 12, 2)->default(0);
            $table->decimal('monthly_output_usd', 12, 2)->default(0);
            $table->decimal('base_monthly_return_rate', 8, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('miners');
    }
};
