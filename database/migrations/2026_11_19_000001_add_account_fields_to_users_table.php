<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 1: OTP verification, deactivation, AI credits and login tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('otp_code')->nullable()->after('remember_token'); // hashed 6-digit code
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->unsignedTinyInteger('otp_attempts')->default(0)->after('otp_expires_at');
            $table->unsignedInteger('ai_credits')->default(0)->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('ai_credits');
            $table->timestamp('last_login_at')->nullable()->after('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'otp_code', 'otp_expires_at', 'otp_attempts', 'ai_credits', 'deactivated_at', 'last_login_at']);
        });
    }
};
