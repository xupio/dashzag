<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_messages', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->after('reply_to_message_id');
            $table->json('draft_to')->nullable()->after('is_draft');
            $table->json('draft_cc')->nullable()->after('draft_to');
        });
    }

    public function down(): void
    {
        Schema::table('internal_messages', function (Blueprint $table) {
            $table->dropColumn(['is_draft', 'draft_to', 'draft_cc']);
        });
    }
};
