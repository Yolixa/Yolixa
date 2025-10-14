<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear table
        DB::table('wallet_types')->truncate();

        DB::table('wallet_types')->insert([
            [
                'blockchain_id' => '1',
                'name' => 'Freighter',
                'slug' => 'freighter',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blockchain_id' => 1,
                'name' => 'Rabet',
                'slug' => 'rabet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blockchain_id' => 1,
                'name' => 'WalletConnect',
                'slug' => 'walletconnect',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
