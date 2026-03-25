<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('btc_wallet_address')->nullable()->after('profile_photo_path');
            $table->string('usdt_wallet_address')->nullable()->after('btc_wallet_address');
            $table->text('bank_transfer_details')->nullable()->after('usdt_wallet_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'btc_wallet_address',
                'usdt_wallet_address',
                'bank_transfer_details',
            ]);
        });
    }
};
