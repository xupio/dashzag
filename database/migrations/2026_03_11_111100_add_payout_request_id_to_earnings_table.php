<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->foreignId('payout_request_id')->nullable()->after('investment_id')->constrained('payout_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_request_id');
        });
    }
};
