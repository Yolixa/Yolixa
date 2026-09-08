<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateWallets = DB::table('wallets')
            ->select('public_key')
            ->whereNotNull('public_key')
            ->groupBy('public_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('public_key');

        foreach ($duplicateWallets as $publicKey) {
            $walletIds = DB::table('wallets')
                ->where('public_key', $publicKey)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->pluck('id');

            $keepId = $walletIds->first();
            DB::table('wallets')
                ->where('public_key', $publicKey)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('wallets', function (Blueprint $table) {
            $table->unique('public_key');
            $table->index(['user_id', 'wallet_type_id']);
        });

        Schema::table('tips', function (Blueprint $table) {
            $table->index(['receiver_id', 'status', 'created_at']);
            $table->index(['sender_wallet', 'receiver_wallet']);
        });
    }

    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->dropIndex(['sender_wallet', 'receiver_wallet']);
            $table->dropIndex(['receiver_id', 'status', 'created_at']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'wallet_type_id']);
            $table->dropUnique(['public_key']);
        });
    }
};
