<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('miner_performance_logs', function (Blueprint $table) {
            $table->decimal('btc_price_usd', 14, 2)->default(0)->after('hashrate_th');
        });
    }

    public function down(): void
    {
        Schema::table('miner_performance_logs', function (Blueprint $table) {
            $table->dropColumn('btc_price_usd');
        });
    }
};
