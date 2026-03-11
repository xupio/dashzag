<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->decimal('team_bonus_rate', 6, 4)->default(0)->after('level_bonus_rate');
        });
    }

    public function down(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropColumn('team_bonus_rate');
        });
    }
};
