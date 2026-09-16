<?php

namespace App\Ussd\States\Levies\Market;

use App\Ussd\Support\ErrorRetryTrait;
use Sparors\Ussd\State;

class MarketTableCountState extends State
{
    use ErrorRetryTrait;

    private const MIN = 1;
    private const MAX = 20;

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $this->menu->text('Enter number of Tables:');
    }

    protected function afterRendering(string $argument): void
    {
        $value = trim($argument);

        if (!ctype_digit($value) || (int) $value < self::MIN || (int) $value > self::MAX) {
            $this->fail('Invalid number. Please enter a whole number between ' . self::MIN . ' and ' . self::MAX . ".\n\nEnter number of Tables:");

            return;
        }

        $this->record->set('market_table_count', (int) $value);
        $this->record->set('market_table_index', 1);
        $this->record->set('market_tables_list', []);
        $this->decision->any(MarketTableNumberState::class);
    }
}
