<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('requested_at');
            $table->string('transaction_reference')->nullable()->after('destination');
            $table->text('admin_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'transaction_reference', 'admin_notes']);
        });
    }
};
