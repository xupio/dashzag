<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_daily_digest_sent_at')->nullable()->after('notification_preferences');
            $table->timestamp('last_weekly_digest_sent_at')->nullable()->after('last_daily_digest_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_daily_digest_sent_at', 'last_weekly_digest_sent_at']);
        });
    }
};