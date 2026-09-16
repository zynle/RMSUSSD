<?php

/*
|--------------------------------------------------------------------------
| Zambia administrative reference data (KYC registration menus)
|--------------------------------------------------------------------------
| Choma District (Southern Province) carries full, realistic ward-level
| detail since Choma Council is the pilot council for this prototype.
| Other districts carry a representative subset sufficient to exercise
| every branch of the registration journey end-to-end.
*/

return [
    'provinces' => [
        'Southern', 'Copperbelt', 'Central', 'Lusaka', 'North-Western',
    ],

    'districts' => [
        'Southern' => ['Choma', 'Livingstone', 'Mazabuka', 'Kalomo', 'Monze'],
        'Copperbelt' => ['Ndola', 'Kitwe', 'Chingola', 'Chililabombwe', 'Luanshya'],
        'Central' => ['Kabwe', 'Kapiri Mposhi', 'Mkushi', 'Serenje'],
        'Lusaka' => ['Lusaka', 'Kafue', 'Chongwe', 'Chilanga'],
        'North-Western' => ['Solwezi', 'Mwinilunga', 'Zambezi', 'Kasempa'],
    ],

    'constituencies' => [
        'Choma' => ['Choma Central', 'Pemba'],
        'Livingstone' => ['Livingstone'],
        'Mazabuka' => ['Mazabuka Central'],
        'Kalomo' => ['Kalomo Central', 'Dundumwezi'],
        'Monze' => ['Monze Central'],
        // Representative defaults for districts outside the Choma pilot
        '__default' => ['Central'],
    ],

    'wards' => [
        'Choma Central' => ['Choma Central Ward', 'Njola', 'Nakazwe', 'Simaubi', 'Batoka Road'],
        'Pemba' => ['Pemba Central', 'Chitongo'],
        // Representative defaults for constituencies outside the Choma pilot
        '__default' => ['Ward 1', 'Ward 2', 'Ward 3'],
    ],
];
