<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('rank');
            $table->decimal('bonus_rate', 8, 4)->default(0);
            $table->unsignedInteger('minimum_referrals')->default(0);
            $table->decimal('minimum_investment', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};
