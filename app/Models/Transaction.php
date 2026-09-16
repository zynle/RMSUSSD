<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'reference', 'session_id', 'phone', 'service_category', 'breakdown',
        'amount', 'payment_method', 'status', 'gateway_reference',
        'gateway_response', 'completed_at',
    ];

    protected $casts = [
        'breakdown' => 'array',
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];
}
