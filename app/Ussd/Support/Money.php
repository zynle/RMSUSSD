<?php

namespace App\Ussd\Support;

class Money
{
    public static function fmt(float $amount): string
    {
        return 'ZMW ' . number_format($amount, 2);
    }
}
