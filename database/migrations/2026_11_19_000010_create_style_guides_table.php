<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 9: brand style guide (one per brand kit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_kit_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('preset', 20)->default('modern'); // classic | modern | minimal | bold
            $table->json('sections');
            $table->json('rules');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_guides');
    }
};
