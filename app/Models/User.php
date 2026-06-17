<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'public_key',
        'referral_key',
        'role',
        'status',
        'bio',
        'verified',
        'category',
        'preferred_tip_asset',
        'min_tip_amount',
        'custom_thank_you_message',
        'goal_title',
        'goal_amount',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'verified' => 'boolean',
        ];
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function tipsReceived()
    {
        return $this->hasMany(Tip::class, 'receiver_id');
    }

    public function tipsSent()
    {
        return $this->hasMany(Tip::class, 'sender_id');
    }
}
