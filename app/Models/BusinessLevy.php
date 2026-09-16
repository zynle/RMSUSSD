<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessLevy extends Model
{
    protected $fillable = ['phone', 'business_name', 'status', 'employees_count', 'trading_centre'];
}
