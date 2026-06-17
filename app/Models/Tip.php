<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:8',
        'platform_fee' => 'decimal:8',
        'network_fee' => 'decimal:8',
        'confirmed_at' => 'datetime',
        'stellar_meta' => 'array',
        'is_anonymous' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
