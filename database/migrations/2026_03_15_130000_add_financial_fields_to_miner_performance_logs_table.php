<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('miner_performance_logs', function (Blueprint $table) {
            $table->decimal('electricity_cost_usd', 12, 2)->default(0)->after('revenue_usd');
            $table->decimal('maintenance_cost_usd', 12, 2)->default(0)->after('electricity_cost_usd');
            $table->decimal('net_profit_usd', 12, 2)->default(0)->after('maintenance_cost_usd');
            $table->unsignedInteger('active_shares')->default(0)->after('uptime_percentage');
            $table->decimal('revenue_per_share_usd', 12, 4)->default(0)->after('active_shares');
            $table->string('source', 30)->default('manual')->after('revenue_per_share_usd');
            $table->timestamp('auto_generated_at')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('miner_performance_logs', function (Blueprint $table) {
            $table->dropColumn([
                'electricity_cost_usd',
                'maintenance_cost_usd',
                'net_profit_usd',
                'active_shares',
                'revenue_per_share_usd',
                'source',
                'auto_generated_at',
            ]);
        });
    }
};
