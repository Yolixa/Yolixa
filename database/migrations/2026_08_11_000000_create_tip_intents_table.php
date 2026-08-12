<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tip_intents', function (Blueprint $table) {
            $table->id();
            $table->string('sender_wallet', 100);
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->string('receiver_wallet', 100);
            $table->string('asset', 20)->default('XLM');
            $table->string('token_contract_id', 100);
            $table->decimal('amount', 18, 7);
            $table->string('amount_atomic', 40);
            $table->unsignedBigInteger('contract_tip_id')->nullable();
            $table->string('status', 40)->default('pending');
            $table->string('tx_hash', 120)->nullable()->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['sender_wallet', 'status']);
            $table->index(['receiver_id', 'status']);
            $table->unique(['sender_wallet', 'contract_tip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tip_intents');
    }
};
