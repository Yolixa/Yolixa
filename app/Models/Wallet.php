<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function blockchain()
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function walletType()
    {
        return $this->belongsTo(WalletType::class);
    }
}
