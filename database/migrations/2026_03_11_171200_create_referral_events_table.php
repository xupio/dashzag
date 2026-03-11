<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_investment_id')->nullable()->constrained('user_investments')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_events');
    }
};
