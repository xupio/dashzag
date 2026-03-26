<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('admin_two_factor_secret')->nullable()->after('profile_photo_path');
            $table->timestamp('admin_two_factor_confirmed_at')->nullable()->after('admin_two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'admin_two_factor_secret',
                'admin_two_factor_confirmed_at',
            ]);
        });
    }
};
