<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 8: AI-generated brand identity kits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('business_name');
            $table->string('industry', 60);
            $table->string('audience')->nullable();
            $table->string('personality', 30);
            $table->string('tagline')->nullable();
            $table->json('colors');
            $table->json('typography');
            $table->json('logo');
            $table->text('voice')->nullable();
            $table->text('mission')->nullable();
            $table->json('keywords')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_kits');
    }
};
