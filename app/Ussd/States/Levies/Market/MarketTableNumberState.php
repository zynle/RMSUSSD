<?php

namespace App\Ussd\States\Levies\Market;

use App\Ussd\Support\ErrorRetryTrait;
use Sparors\Ussd\State;

class MarketTableNumberState extends State
{
    use ErrorRetryTrait;

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();
        $index = (int) $this->record->get('market_table_index', 1);

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $this->menu->text("Table No.: for Table {$index}.");
    }

    protected function afterRendering(string $argument): void
    {
        $value = trim($argument);
        $index = (int) $this->record->get('market_table_index', 1);
        $count = (int) $this->record->get('market_table_count', 1);

        if ($value === '') {
            $this->fail("Table number cannot be empty.\n\nTable No.: for Table {$index}.");

            return;
        }

        $list = $this->record->get('market_tables_list', []);
        $list[] = $value;
        $this->record->set('market_tables_list', $list);

        if ($index < $count) {
            $this->record->set('market_table_index', $index + 1);
            $this->decision->any(self::class);

            return;
        }

        $this->decision->any(BuildMarketLevyCartAction::class);
    }
}
