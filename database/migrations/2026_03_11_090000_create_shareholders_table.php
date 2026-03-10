<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('package_name', 50);
            $table->decimal('price', 10, 2);
            $table->string('billing_cycle', 20)->default('monthly');
            $table->unsignedInteger('units_limit');
            $table->string('status', 30)->default('active');
            $table->timestamp('subscribed_at');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shareholders');
    }
};
