<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
