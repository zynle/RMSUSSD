<?php

namespace Database\Seeders;

use App\Models\LevyRate;
use Illuminate\Database\Seeder;

class LevyRateSeeder extends Seeder
{
    /**
     * When USSD_TEST_LOW_RATES=true (see .env), every rate below is
     * overridden to USSD_TEST_RATE_AMOUNT (default 1.00) so real mobile
     * money payments can be tested end-to-end for a few ngwee/kwacha
     * instead of the real council fee schedule. Never enable this in
     * production — it exists purely for live-payment smoke testing.
     */
    public function run(): void
    {
        $testMode = filter_var(env('USSD_TEST_LOW_RATES', false), FILTER_VALIDATE_BOOLEAN);
        $testAmount = (float) env('USSD_TEST_RATE_AMOUNT', 1);

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

            // Once-off applications. These are deliberately available before
            // ratepayer registration; the mobile-money outcome SMS is their receipt.
            ['category' => 'once_off', 'code' => 'once_off_business_premises', 'label' => 'Business Premises / Trading Licence', 'unit_label' => 'application', 'rate' => 1600],
            ['category' => 'once_off', 'code' => 'once_off_building_plan', 'label' => 'Building Plan Approval', 'unit_label' => 'application', 'rate' => 2500],
            ['category' => 'once_off', 'code' => 'once_off_planning_permission', 'label' => 'Planning / Development Permission', 'unit_label' => 'application', 'rate' => 1500],
            ['category' => 'once_off', 'code' => 'once_off_change_land_use', 'label' => 'Change of Land Use', 'unit_label' => 'application', 'rate' => 3000],
            ['category' => 'once_off', 'code' => 'once_off_subdivision', 'label' => 'Subdivision / Consolidation of Plots', 'unit_label' => 'application', 'rate' => 5000],
            ['category' => 'once_off', 'code' => 'once_off_occupancy_certificate', 'label' => 'Occupancy Certificate', 'unit_label' => 'application', 'rate' => 1200],
            ['category' => 'once_off', 'code' => 'once_off_fire_safety', 'label' => 'Fire Safety Inspection / Certificate', 'unit_label' => 'application', 'rate' => 750],
            ['category' => 'once_off', 'code' => 'once_off_outdoor_advertising', 'label' => 'Outdoor Advertising / Billboard Permit', 'unit_label' => 'application', 'rate' => 500],
            ['category' => 'once_off', 'code' => 'once_off_event_permit', 'label' => 'Event / Public Gathering Permit', 'unit_label' => 'application', 'rate' => 1000],
            ['category' => 'once_off', 'code' => 'once_off_liquor_licence', 'label' => 'Liquor Licence Application', 'unit_label' => 'application', 'rate' => 5000],
            ['category' => 'once_off', 'code' => 'once_off_burial_exhumation', 'label' => 'Burial / Exhumation Application', 'unit_label' => 'application', 'rate' => 300],
        ];

        // Codes that must be zeroed out (rather than set to $testAmount) in
        // test mode so a composite total — e.g. Business Levy, which is
        // normally Levy + Fire + Health + Personal Levy x employees — comes
        // to exactly K{$testAmount} flat instead of summing several K1
        // components into something much larger.
        $zeroInTestMode = [
            'business_new_fire', 'business_new_health', 'business_personal_levy',
            'business_renewal_fire', 'business_renewal_health',
        ];

        foreach ($rates as $rate) {
            if ($testMode) {
                $rate['rate'] = in_array($rate['code'], $zeroInTestMode, true) ? 0 : $testAmount;
            }

            LevyRate::updateOrCreate(['code' => $rate['code']], $rate + ['is_active' => true]);
        }
    }
}
