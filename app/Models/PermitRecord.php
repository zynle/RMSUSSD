<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermitRecord extends Model
{
    protected $table = 'permits';

    protected $fillable = [
        'phone', 'permit_no', 'holder_name', 'nationality', 'company', 'type', 'amount', 'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
