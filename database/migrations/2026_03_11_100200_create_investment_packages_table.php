<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('miner_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('shares_count');
            $table->unsignedInteger('units_limit');
            $table->decimal('monthly_return_rate', 8, 4)->default(0);
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_packages');
    }
};
