<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 10: content manager posts with scheduling and reminders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('caption');
            $table->string('hashtags', 500)->nullable();
            $table->string('platform', 20)->default('facebook'); // facebook | instagram | both
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('draft'); // draft | scheduled | published
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedSmallInteger('remind_before')->nullable(); // minutes before scheduled_at
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
