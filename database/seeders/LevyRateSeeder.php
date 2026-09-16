<?php

namespace Database\Seeders;

use App\Models\LevyRate;
use Illuminate\Database\Seeder;

class LevyRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            // Business levy
            ['category' => 'business', 'code' => 'business_new_levy', 'label' => 'New Business Levy', 'unit_label' => 'flat', 'rate' => 1600],
            ['category' => 'business', 'code' => 'business_new_fire', 'label' => 'New Business Fire Levy', 'unit_label' => 'flat', 'rate' => 350],
            ['category' => 'business', 'code' => 'business_new_health', 'label' => 'New Business Health Levy', 'unit_label' => 'flat', 'rate' => 580],
            ['category' => 'business', 'code' => 'business_renewal_levy', 'label' => 'Business Levy Renewal', 'unit_label' => 'flat', 'rate' => 500],
            ['category' => 'business', 'code' => 'business_renewal_fire', 'label' => 'Business Fire Levy Renewal', 'unit_label' => 'flat', 'rate' => 150],
            ['category' => 'business', 'code' => 'business_renewal_health', 'label' => 'Business Health Levy Renewal', 'unit_label' => 'flat', 'rate' => 320],
            ['category' => 'business', 'code' => 'business_personal_levy', 'label' => 'Personal Levy', 'unit_label' => 'per employee', 'rate' => 50],

            // Market levy
            ['category' => 'market', 'code' => 'market_table', 'label' => 'Market Table Levy', 'unit_label' => 'per table', 'rate' => 5],

            // Barrier - Livestock
            ['category' => 'barrier', 'code' => 'barrier_livestock_goat', 'label' => 'Goat Levy', 'unit_label' => 'per goat', 'rate' => 20],
            ['category' => 'barrier', 'code' => 'barrier_livestock_cow', 'label' => 'Cow Levy', 'unit_label' => 'per cow', 'rate' => 100],
            ['category' => 'barrier', 'code' => 'barrier_livestock_sheep', 'label' => 'Sheep Levy', 'unit_label' => 'per sheep', 'rate' => 25],

            // Barrier - Timber
            ['category' => 'barrier', 'code' => 'barrier_timber_log', 'label' => 'Timber Log Levy', 'unit_label' => 'per log', 'rate' => 15],
            ['category' => 'barrier', 'code' => 'barrier_timber_plank', 'label' => 'Timber Plank Levy', 'unit_label' => 'per plank', 'rate' => 10],

            // Barrier - Opaque beer
            ['category' => 'barrier', 'code' => 'barrier_beer_tonne', 'label' => 'Opaque Beer Levy (Tonne)', 'unit_label' => 'per tonne', 'rate' => 200],
            ['category' => 'barrier', 'code' => 'barrier_beer_drum', 'label' => 'Opaque Beer Levy (Drum)', 'unit_label' => 'per drum', 'rate' => 50],
            ['category' => 'barrier', 'code' => 'barrier_beer_20l', 'label' => 'Opaque Beer Levy (20L)', 'unit_label' => 'per 20L', 'rate' => 15],

            // Barrier - Grain / Mast
            ['category' => 'barrier', 'code' => 'barrier_grain_bag', 'label' => 'Grain Levy', 'unit_label' => 'per bag', 'rate' => 10],
            ['category' => 'barrier', 'code' => 'barrier_mast_unit', 'label' => 'Mast Levy', 'unit_label' => 'per mast', 'rate' => 30],
        ];

        foreach ($rates as $rate) {
            LevyRate::updateOrCreate(['code' => $rate['code']], $rate + ['is_active' => true]);
        }
    }
}
