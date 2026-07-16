<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_orders', function (Blueprint $table) {
            $table->string('gateway_provider')->nullable()->after('payment_method');
            $table->string('gateway_reference')->nullable()->after('payment_reference')->index();
            $table->string('gateway_status')->nullable()->after('gateway_reference');
            $table->text('gateway_redirect_url')->nullable()->after('gateway_status');
            $table->text('gateway_embedded_url')->nullable()->after('gateway_redirect_url');
            $table->json('gateway_payload')->nullable()->after('gateway_embedded_url');
            $table->timestamp('gateway_paid_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('investment_orders', function (Blueprint $table) {
            $table->dropIndex(['gateway_reference']);
            $table->dropColumn([
                'gateway_provider',
                'gateway_reference',
                'gateway_status',
                'gateway_redirect_url',
                'gateway_embedded_url',
                'gateway_payload',
                'gateway_paid_at',
            ]);
        });
    }
};
