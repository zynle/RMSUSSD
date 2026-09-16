<?php

namespace Database\Seeders;

use App\Models\BusinessLevy;
use App\Models\Council;
use App\Models\LicenseRecord;
use App\Models\PermitRecord;
use App\Models\PropertyRecord;
use App\Models\Ratepayer;
use Illuminate\Database\Seeder;

/**
 * Realistic Choma Council demo/test data. All registered ratepayers below
 * share the demo PIN 1234 so the full payment journey can be exercised
 * end-to-end without needing to run the KYC flow first. One phone number
 * (260977000099) is deliberately left unregistered to exercise the KYC
 * registration journey. See docs/README for the full test matrix.
 */
class ChomaDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $council = Council::where('code', 'CHOMA')->first();

        // Same USSD_TEST_LOW_RATES switch as LevyRateSeeder — when set, the
        // property/license/permit amounts below (which aren't driven by the
        // levy_rates table) are also collapsed to USSD_TEST_RATE_AMOUNT so a
        // live payment test costs a few ngwee/kwacha instead of thousands.
        $testMode = filter_var(env('USSD_TEST_LOW_RATES', false), FILTER_VALIDATE_BOOLEAN);
        $testAmount = (float) env('USSD_TEST_RATE_AMOUNT', 1);

        $ratepayers = [
            [
                'phone' => '260977000001',
                'first_name' => 'William',
                'last_name' => 'Phiri',
                'gender' => 'male',
                'province' => 'Southern',
                'district' => 'Choma',
                'constituency' => 'Choma Central',
                'ward' => 'Njola',
                'market_name' => 'Soweto Market',
                'shop_no' => '0086',
                'location' => 'Choma',
            ],
            [
                'phone' => '260977000002',
                'first_name' => 'Oxylane',
                'last_name' => 'Digital Solutions Ltd',
                'gender' => 'male',
                'province' => 'Southern',
                'district' => 'Choma',
                'constituency' => 'Choma Central',
                'ward' => 'Choma Central Ward',
                'location' => 'Choma Central Business District',
            ],
            [
                'phone' => '260977000003',
                'first_name' => 'Kumawa',
                'last_name' => 'Farms Ltd',
                'gender' => 'male',
                'province' => 'Southern',
                'district' => 'Choma',
                'constituency' => 'Choma Central',
                'ward' => 'Nakazwe',
                'location' => 'Choma',
            ],
            [
                'phone' => '260977000004',
                'first_name' => 'Namwela',
                'last_name' => 'Zanga',
                'gender' => 'female',
                'province' => 'Southern',
                'district' => 'Choma',
                'constituency' => 'Choma Central',
                'ward' => 'Simaubi',
                'location' => 'Choma',
            ],
            [
                'phone' => '260977000005',
                'first_name' => 'Juma',
                'last_name' => 'Azizi',
                'gender' => 'male',
                'province' => 'Southern',
                'district' => 'Choma',
                'constituency' => 'Choma Central',
                'ward' => 'Batoka Road',
                'location' => 'Choma',
            ],
            [
                'phone' => '260977000006',
                'first_name' => 'Mary',
                'last_name' => 'Banda',
                'gender' => 'female',
                'province' => 'Southern',
                'district' => 'Choma',
                'constituency' => 'Pemba',
                'ward' => 'Pemba Central',
                'market_name' => 'Choma Main Market',
                'shop_no' => '0021',
                'location' => 'Choma',
            ],
        ];

        foreach ($ratepayers as $data) {
            Ratepayer::updateOrCreate(
                ['phone' => $data['phone']],
                $data + [
                    'council_id' => $council?->id,
                    'pin' => '1234',
                    'is_registered' => true,
                    'registered_at' => now(),
                ]
            );
        }

        // Business levy account for renewal-flow testing (260977000002)
        BusinessLevy::updateOrCreate(
            ['phone' => '260977000002', 'business_name' => 'Oxylane Digital Solutions Ltd'],
            ['status' => 'existing', 'employees_count' => 10, 'trading_centre' => 'Choma Central']
        );

        // Property rates for 260977000003 (Kumawa Farms + Planet Auto Spares)
        PropertyRecord::updateOrCreate(
            ['plot_no' => '2604B'],
            [
                'phone' => '260977000003',
                'owner_name' => 'Kumawa Farms',
                'rate_type' => 'property_rates',
                'balance_bf' => $testMode ? 0 : 450.00,
                'charge' => $testMode ? $testAmount : 2500.00,
                'status' => 'unpaid',
            ]
        );
        PropertyRecord::updateOrCreate(
            ['plot_no' => '0053HD'],
            [
                'phone' => '260977000003',
                'owner_name' => 'Planet Auto Spares',
                'rate_type' => 'property_rates',
                'balance_bf' => $testMode ? 0 : 450.00,
                'charge' => $testMode ? $testAmount : 3000.00,
                'status' => 'unpaid',
            ]
        );

        // License for 260977000004 (Namwela Zanga Liquor Store)
        LicenseRecord::updateOrCreate(
            ['license_no' => '001234ZM'],
            [
                'phone' => '260977000004',
                'holder_name' => 'Namwela Zanga Liquor Store',
                'store_no' => '2604',
                'district' => 'Choma',
                'type' => 'Liquor License',
                'amount' => $testMode ? $testAmount : 5000.00,
                'status' => 'unpaid',
            ]
        );

        // Permit for 260977000005 (Juma Azizi)
        PermitRecord::updateOrCreate(
            ['permit_no' => 'WP-2604'],
            [
                'phone' => '260977000005',
                'holder_name' => 'Juma Azizi',
                'nationality' => 'Kenyan',
                'company' => 'Oxylane Digital',
                'type' => 'Work Permit',
                'amount' => $testMode ? $testAmount : 5000.00,
                'status' => 'unpaid',
            ]
        );

        // 260977000099 is deliberately left unregistered — used to exercise
        // the full KYC registration journey during testing.
    }
}
