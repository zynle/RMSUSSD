<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyRecord extends Model
{
    protected $table = 'properties';

    protected $fillable = [
        'phone', 'plot_no', 'owner_name', 'rate_type', 'balance_bf', 'charge', 'status',
    ];

    protected $casts = [
        'balance_bf' => 'decimal:2',
        'charge' => 'decimal:2',
    ];

    public function totalDue(): float
    {
        return (float) $this->balance_bf + (float) $this->charge;
    }
}
