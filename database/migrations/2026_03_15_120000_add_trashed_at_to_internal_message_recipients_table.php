<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_message_recipients', function (Blueprint $table) {
            $table->timestamp('trashed_at')->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('internal_message_recipients', function (Blueprint $table) {
            $table->dropColumn('trashed_at');
        });
    }
};
