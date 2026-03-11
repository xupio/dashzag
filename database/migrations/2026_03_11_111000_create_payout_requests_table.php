<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 50);
            $table->string('destination');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};
