<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    // ✅ Single vendor() — no duplicate
    // firm_name lives on the vendors table via this relationship
    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'user_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
}
