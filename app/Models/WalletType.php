<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletType extends Model
{
    protected $guarded=[];

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }
}
