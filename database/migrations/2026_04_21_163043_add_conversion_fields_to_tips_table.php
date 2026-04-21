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
        Schema::table('tips', function (Blueprint $table) {
            $table->decimal('conversion_rate', 18, 8)->nullable()->after('asset');
            $table->decimal('converted_ylx_amount', 18, 8)->nullable()->after('conversion_rate');
            $table->decimal('creator_payout_amount', 18, 8)->nullable()->after('converted_ylx_amount');
            $table->string('payout_status')->default('pending')->after('status');
            $table->string('payout_tx_hash')->nullable()->after('payout_status');
            $table->text('payout_error')->nullable()->after('payout_tx_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->dropColumn([
                'conversion_rate',
                'converted_ylx_amount',
                'creator_payout_amount',
                'payout_status',
                'payout_tx_hash',
                'payout_error'
            ]);
        });
    }
};
