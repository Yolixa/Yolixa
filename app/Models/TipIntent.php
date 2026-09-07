<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipIntent extends Model
{
    protected $fillable = [
        'sender_wallet',
        'receiver_id',
        'receiver_wallet',
        'asset',
        'token_contract_id',
        'amount',
        'amount_atomic',
        'contract_tip_id',
        'status',
        'tx_hash',
        'expires_at',
        'confirmed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:7',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function tip()
    {
        return $this->hasOne(Tip::class);
    }
}
