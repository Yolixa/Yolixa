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
        Schema::create('wallet_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blockchain_id')->constrained('blockchains')->onDelete('cascade');
            $table->string('name', 100)->unique(); // e.g. Freighter, MetaMask, Phantom
            $table->string('slug', 50)->unique(); // lowercase identifier e.g. freighter, metamask
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_types');
    }
};
