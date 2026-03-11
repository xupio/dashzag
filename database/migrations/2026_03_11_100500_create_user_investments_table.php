<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('miner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('investment_packages')->cascadeOnDelete();
            $table->foreignId('shareholder_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('shares_owned');
            $table->decimal('monthly_return_rate', 8, 4)->default(0);
            $table->decimal('level_bonus_rate', 8, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamp('subscribed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_investments');
    }
};
