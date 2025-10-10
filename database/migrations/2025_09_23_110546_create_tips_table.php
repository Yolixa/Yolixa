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
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->string('tx_hash', 120)->unique();
            $table->decimal('amount', 18, 8);
            $table->decimal('bonus', 18, 8)->default(0);
            $table->string('asset', 20)->default('USDC'); // XLM, USDC etc.
            $table->decimal('platform_fee', 18, 8);
            $table->enum('status', ['pending','confirmed','failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
