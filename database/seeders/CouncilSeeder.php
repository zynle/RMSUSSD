<?php

namespace Database\Seeders;

use App\Models\Council;
use Illuminate\Database\Seeder;

class CouncilSeeder extends Seeder
{
    public function run(): void
    {
        Council::updateOrCreate(
            ['code' => 'CHOMA'],
            [
                'name' => 'Choma Council',
                'ussd_shortcode' => '*262*22#',
                'sms_sender_id' => 'ChomaLGA',
                'is_active' => true,
            ]
        );
    }
}
