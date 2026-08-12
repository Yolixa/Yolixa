<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipIntent extends Model
{
    protected $guarded = [];

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
