<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 5: subscription plans, subscriptions and the AI credit ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('slug', 40)->unique();
            $table->unsignedInteger('price')->default(0); // BDT per month
            $table->unsignedInteger('monthly_credits')->default(0);
            $table->unsignedInteger('team_limit')->nullable(); // null = unlimited
            $table->unsignedInteger('brand_kit_limit')->nullable(); // null = unlimited
            $table->json('features')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active'); // active | cancelled | expired
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount'); // + added, - spent
            $table->unsignedInteger('balance_after');
            $table->string('reason');
            $table->timestamps();
        });

        // The four plans are reference data, so they are created together with the table.
        $now = now();
        $plans = [
            ['Free', 'free', 0, 20, 0, 1, ['20 AI credits per month', '1 brand kit', 'Style guide with PDF export', 'Content manager and reminders']],
            ['Basic', 'basic', 499, 150, 2, 3, ['150 AI credits per month', 'Up to 3 brand kits', '2 team members', 'Bangla and English captions']],
            ['Premium', 'premium', 1499, 600, 5, 10, ['600 AI credits per month', 'Up to 10 brand kits', '5 team members', 'Priority support']],
            ['Enterprise', 'enterprise', 4999, 3000, null, null, ['3,000 AI credits per month', 'Unlimited brand kits', 'Unlimited team members', 'Dedicated account manager']],
        ];

        foreach ($plans as $i => [$name, $slug, $price, $credits, $team, $kits, $features]) {
            DB::table('plans')->insert([
                'name' => $name,
                'slug' => $slug,
                'price' => $price,
                'monthly_credits' => $credits,
                'team_limit' => $team,
                'brand_kit_limit' => $kits,
                'features' => json_encode($features),
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
