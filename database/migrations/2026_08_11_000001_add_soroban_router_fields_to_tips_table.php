<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            if (!Schema::hasColumn('tips', 'tip_intent_id')) {
                $table->foreignId('tip_intent_id')->nullable()->after('id')->constrained('tip_intents')->nullOnDelete();
            }

            if (!Schema::hasColumn('tips', 'router_contract_id')) {
                $table->string('router_contract_id', 100)->nullable()->after('soroban_tx_hash');
            }

            if (!Schema::hasColumn('tips', 'token_contract_id')) {
                $table->string('token_contract_id', 100)->nullable()->after('router_contract_id');
            }

            if (!Schema::hasColumn('tips', 'contract_tip_id')) {
                $table->unsignedBigInteger('contract_tip_id')->nullable()->after('token_contract_id');
            }
        });

        Schema::table('tips', function (Blueprint $table) {
            $table->unique('tip_intent_id');
            $table->index(['sender_wallet', 'contract_tip_id']);
            $table->index('router_contract_id');
        });
    }

    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->dropIndex(['router_contract_id']);
            $table->dropIndex(['sender_wallet', 'contract_tip_id']);
            $table->dropUnique(['tip_intent_id']);
        });

        Schema::table('tips', function (Blueprint $table) {
            foreach (['contract_tip_id', 'token_contract_id', 'router_contract_id'] as $column) {
                if (Schema::hasColumn('tips', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('tips', 'tip_intent_id')) {
                $table->dropConstrainedForeignId('tip_intent_id');
            }
        });
    }
};
