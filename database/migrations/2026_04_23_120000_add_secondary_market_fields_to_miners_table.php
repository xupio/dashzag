<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('miners', function (Blueprint $table) {
            $table->unsignedInteger('shares_sold')->default(0)->after('total_shares');
            $table->unsignedInteger('near_capacity_threshold_percent')->default(90)->after('status');
            $table->unsignedInteger('maturity_days')->default(90)->after('near_capacity_threshold_percent');
            $table->decimal('secondary_market_fee_percent', 5, 2)->default(5)->after('maturity_days');
            $table->timestamp('opened_at')->nullable()->after('started_at');
            $table->timestamp('sold_out_at')->nullable()->after('opened_at');
            $table->timestamp('matured_at')->nullable()->after('sold_out_at');
            $table->timestamp('secondary_market_opened_at')->nullable()->after('matured_at');
            $table->timestamp('closed_at')->nullable()->after('secondary_market_opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('miners', function (Blueprint $table) {
            $table->dropColumn([
                'shares_sold',
                'near_capacity_threshold_percent',
                'maturity_days',
                'secondary_market_fee_percent',
                'opened_at',
                'sold_out_at',
                'matured_at',
                'secondary_market_opened_at',
                'closed_at',
            ]);
        });
    }
};
