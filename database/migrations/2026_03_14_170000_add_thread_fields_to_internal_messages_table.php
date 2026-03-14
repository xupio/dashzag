<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_messages', function (Blueprint $table) {
            $table->foreignId('thread_root_id')->nullable()->after('sender_id')->constrained('internal_messages')->nullOnDelete();
            $table->foreignId('reply_to_message_id')->nullable()->after('thread_root_id')->constrained('internal_messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('internal_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_message_id');
            $table->dropConstrainedForeignId('thread_root_id');
        });
    }
};
