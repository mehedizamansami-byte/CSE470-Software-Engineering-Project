<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 3: portfolio for designers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline');
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->unsignedInteger('starting_price')->nullable(); // BDT
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('location', 80)->nullable();
            $table->string('portfolio_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 60)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
        Schema::dropIfExists('designer_profiles');
    }
};
