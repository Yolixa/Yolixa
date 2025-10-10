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
        DB::table('wallet_types')->insert([
            [
                'blockchain_id' => '1',
                'name' => 'Freighter',
                'slug' => 'freighter',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
