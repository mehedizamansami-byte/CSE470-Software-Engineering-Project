<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 6: simulated bKash, Nagad and card payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no', 30)->unique();
            $table->string('purpose', 20); // plan | credits
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('credits')->nullable();
            $table->string('description');
            $table->unsignedInteger('amount'); // BDT
            $table->string('method', 10)->nullable(); // bkash | nagad | card
            $table->string('account', 40)->nullable(); // masked wallet number or card
            $table->string('transaction_id', 30)->nullable()->unique();
            $table->string('status', 20)->default('pending'); // pending | awaiting_otp | paid | failed | cancelled
            $table->string('otp_code')->nullable(); // hashed
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
