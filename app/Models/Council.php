<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Council extends Model
{
    protected $fillable = ['name', 'code', 'ussd_shortcode', 'sms_sender_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
