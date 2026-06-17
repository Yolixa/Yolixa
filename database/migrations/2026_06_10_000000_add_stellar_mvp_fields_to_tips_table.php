<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->string('sender_wallet', 100)->nullable()->after('receiver_id');
            $table->string('receiver_wallet', 100)->nullable()->after('sender_wallet');
            $table->decimal('network_fee', 18, 8)->default(0)->after('platform_fee');
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->json('stellar_meta')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->dropColumn([
                'sender_wallet',
                'receiver_wallet',
                'network_fee',
                'confirmed_at',
                'stellar_meta',
            ]);
        });
    }
};
