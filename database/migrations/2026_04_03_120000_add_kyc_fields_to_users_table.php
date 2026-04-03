<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kyc_status')->default('not_submitted')->after('profile_photo_path');
            $table->string('kyc_proof_path')->nullable()->after('kyc_status');
            $table->string('kyc_proof_original_name')->nullable()->after('kyc_proof_path');
            $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_proof_original_name');
            $table->timestamp('kyc_reviewed_at')->nullable()->after('kyc_submitted_at');
            $table->foreignId('kyc_reviewer_user_id')->nullable()->after('kyc_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('kyc_admin_notes')->nullable()->after('kyc_reviewer_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kyc_reviewer_user_id');
            $table->dropColumn([
                'kyc_status',
                'kyc_proof_path',
                'kyc_proof_original_name',
                'kyc_submitted_at',
                'kyc_reviewed_at',
                'kyc_admin_notes',
            ]);
        });
    }
};
