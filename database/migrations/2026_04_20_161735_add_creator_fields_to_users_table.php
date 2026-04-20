<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('social_links')->nullable();
            $table->string('category')->nullable();
            $table->string('preferred_tip_asset', 20)->default('XLM');
            $table->decimal('min_tip_amount', 18, 8)->default(1.0);
            $table->text('custom_thank_you_message')->nullable();
            $table->string('goal_title')->nullable();
            $table->decimal('goal_amount', 18, 8)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('plan_type', ['free', 'pro'])->default('free');
            $table->decimal('ylx_claimable_balance', 18, 8)->default(0);
            $table->decimal('ylx_claimed_total', 18, 8)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_image',
                'cover_image',
                'social_links',
                'category',
                'preferred_tip_asset',
                'min_tip_amount',
                'custom_thank_you_message',
                'goal_title',
                'goal_amount',
                'is_featured',
                'plan_type',
                'ylx_claimable_balance',
                'ylx_claimed_total',
            ]);
        });
    }
};
