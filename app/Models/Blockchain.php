<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blockchain extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'active',
    ];

    public function walletTypes()
    {
        return $this->hasMany(WalletType::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }
}
