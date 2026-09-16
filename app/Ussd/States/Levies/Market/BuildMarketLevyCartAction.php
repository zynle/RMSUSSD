<?php

namespace App\Ussd\States\Levies\Market;

use App\Models\LevyRate;
use App\Ussd\States\Levies\LevyMenuState;
use App\Ussd\States\Shared\ConfirmPaymentState;
use Sparors\Ussd\Action;

class BuildMarketLevyCartAction extends Action
{
    public function run(): string
    {
        $tables = $this->record->get('market_tables_list', []);
        $rate = LevyRate::rate('market_table');

        $items = [];
        foreach ($tables as $tableNo) {
            $items[] = ['label' => "TABLE No.: {$tableNo}", 'amount' => $rate];
        }

        $total = $rate * count($tables);

        $this->record->set('cart_title', 'MARKET LEVY');
        $this->record->set('cart_items', $items);
        $this->record->set('cart_total', $total);
        $this->record->set('cart_category', 'market_levy');
        $this->record->set('cart_meta', ['tables' => $tables]);
        $this->record->set('cart_cancel_next', LevyMenuState::class);

        $this->record->deleteMultiple(['market_table_count', 'market_table_index', 'market_tables_list']);

        return ConfirmPaymentState::class;
    }
}
