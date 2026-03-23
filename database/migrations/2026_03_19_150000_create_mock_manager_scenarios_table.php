<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_manager_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('miner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('investment_packages')->cascadeOnDelete();
            $table->string('name');
            $table->json('inputs');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_manager_scenarios');
    }
};
