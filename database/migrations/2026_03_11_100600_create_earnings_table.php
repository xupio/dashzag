<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_id')->constrained('user_investments')->cascadeOnDelete();
            $table->date('earned_on');
            $table->decimal('amount', 12, 2);
            $table->string('source', 50)->default('mining_return');
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earnings');
    }
};
