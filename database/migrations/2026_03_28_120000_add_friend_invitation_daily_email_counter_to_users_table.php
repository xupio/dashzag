<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('friend_invitation_emails_sent_on')->nullable()->after('last_weekly_digest_sent_at');
            $table->unsignedInteger('friend_invitation_emails_sent_count')->default(0)->after('friend_invitation_emails_sent_on');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'friend_invitation_emails_sent_on',
                'friend_invitation_emails_sent_count',
            ]);
        });
    }
};
