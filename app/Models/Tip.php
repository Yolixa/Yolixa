<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    protected $fillable = [
        'tip_intent_id',
        'sender_id',
        'receiver_id',
        'sender_wallet',
        'receiver_wallet',
        'tx_hash',
        'amount',
        'asset',
        'asset_issuer',
        'platform_fee',
        'network_fee',
        'bonus',
        'reward_ylx_amount',
        'ylx_reward_status',
        'status',
        'confirmed_at',
        'conversion_rate',
        'converted_ylx_amount',
        'creator_payout_amount',
        'payout_status',
        'payout_tx_hash',
        'payout_error',
        'stellar_meta',
        'soroban_status',
        'soroban_tx_hash',
        'soroban_error',
        'soroban_recorded_at',
        'router_contract_id',
        'token_contract_id',
        'contract_tip_id',
        'message',
        'is_anonymous',
        'sender_name',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'platform_fee' => 'decimal:8',
        'network_fee' => 'decimal:8',
        'creator_payout_amount' => 'decimal:8',
        'reward_ylx_amount' => 'decimal:8',
        'confirmed_at' => 'datetime',
        'soroban_recorded_at' => 'datetime',
        'stellar_meta' => 'array',
        'is_anonymous' => 'boolean',
    ];

    public function intent()
    {
        return $this->belongsTo(TipIntent::class, 'tip_intent_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
