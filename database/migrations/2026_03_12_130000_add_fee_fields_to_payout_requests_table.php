<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->decimal('fee_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('net_amount', 12, 2)->default(0)->after('fee_amount');
            $table->decimal('fee_rate', 8, 4)->default(0)->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->dropColumn(['fee_amount', 'net_amount', 'fee_rate']);
        });
    }
};
