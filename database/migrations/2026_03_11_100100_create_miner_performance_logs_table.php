<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('miner_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('miner_id')->constrained()->cascadeOnDelete();
            $table->date('logged_on');
            $table->decimal('revenue_usd', 12, 2)->default(0);
            $table->decimal('hashrate_th', 10, 2)->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(100);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['miner_id', 'logged_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('miner_performance_logs');
    }
};
