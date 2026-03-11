<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investment_id');
            $table->foreignId('investment_id')->nullable()->after('user_id')->constrained('user_investments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investment_id');
            $table->foreignId('investment_id')->constrained('user_investments')->cascadeOnDelete();
        });
    }
};
