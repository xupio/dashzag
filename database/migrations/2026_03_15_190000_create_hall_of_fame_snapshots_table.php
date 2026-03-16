<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_of_fame_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('rank_position');
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('profile_power_score')->default(0);
            $table->string('rank_label')->nullable();
            $table->json('highlights')->nullable();
            $table->timestamps();

            $table->unique(['category', 'period_start', 'user_id']);
            $table->index(['category', 'period_start', 'rank_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_of_fame_snapshots');
    }
};
