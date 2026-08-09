<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            if (!Schema::hasColumn('tips', 'asset_issuer')) {
                $table->string('asset_issuer', 100)->nullable()->after('asset');
            }

            if (!Schema::hasColumn('tips', 'reward_ylx_amount')) {
                $table->decimal('reward_ylx_amount', 18, 8)->default(0)->after('bonus');
            }

            if (!Schema::hasColumn('tips', 'ylx_reward_status')) {
                $table->string('ylx_reward_status', 40)->nullable()->after('reward_ylx_amount');
            }

            if (!Schema::hasColumn('tips', 'soroban_status')) {
                $table->string('soroban_status', 40)->nullable()->after('stellar_meta');
            }

            if (!Schema::hasColumn('tips', 'soroban_tx_hash')) {
                $table->string('soroban_tx_hash', 120)->nullable()->after('soroban_status');
            }

            if (!Schema::hasColumn('tips', 'soroban_error')) {
                $table->text('soroban_error')->nullable()->after('soroban_tx_hash');
            }

            if (!Schema::hasColumn('tips', 'soroban_recorded_at')) {
                $table->timestamp('soroban_recorded_at')->nullable()->after('soroban_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $columns = [
                'asset_issuer',
                'reward_ylx_amount',
                'ylx_reward_status',
                'soroban_status',
                'soroban_tx_hash',
                'soroban_error',
                'soroban_recorded_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tips', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
