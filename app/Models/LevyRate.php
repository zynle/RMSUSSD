<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevyRate extends Model
{
    protected $fillable = ['category', 'code', 'label', 'unit_label', 'rate', 'is_active'];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function rate(string $code): float
    {
        return (float) (static::where('code', $code)->where('is_active', true)->value('rate') ?? 0);
    }
}
