<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseRecord extends Model
{
    protected $table = 'licenses';

    protected $fillable = [
        'phone', 'license_no', 'holder_name', 'store_no', 'district', 'type', 'amount', 'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
