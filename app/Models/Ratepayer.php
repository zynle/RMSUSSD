<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Ratepayer extends Model
{
    protected $fillable = [
        'council_id', 'phone', 'first_name', 'last_name', 'gender',
        'province', 'district', 'constituency', 'ward',
        'market_name', 'shop_no', 'location',
        'pin', 'pin_attempts', 'pin_locked_until',
        'is_registered', 'registered_at',
    ];

    protected $hidden = ['pin'];

    protected $casts = [
        'is_registered' => 'boolean',
        'registered_at' => 'datetime',
        'pin_locked_until' => 'datetime',
    ];

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function setPinAttribute($value): void
    {
        $this->attributes['pin'] = $value ? Hash::make($value) : null;
    }

    public function checkPin(string $pin): bool
    {
        return $this->pin && Hash::check($pin, $this->pin);
    }

    public function isPinLocked(): bool
    {
        return $this->pin_locked_until && $this->pin_locked_until->isFuture();
    }
}
