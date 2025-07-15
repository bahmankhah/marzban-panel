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
        'status',
        'used_traffic',
        'data_limit',
        'data_limit_reset_strategy',
        'expire',
        'lifetime_used_traffic',
        'created_at',
        'links',
        'subscription_url',
        'proxies',
        'inbounds',
        'note',
        'sub_updated_at',
        'sub_last_user_agent',
        'online_at',
        'on_hold_expire_duration',
        'on_hold_timeout',
        'auto_delete_in_days',
        'panel_username',
        'panel_password',
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
        ];
    }
}
