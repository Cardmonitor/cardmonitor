<?php

namespace App\Console\Commands\Card;

use App\Models\Cards\Card;
use Illuminate\Console\Command;

class ImportCommand extends Command
{
    protected $signature = 'card:import {cardmarket_product_id}';

    protected $description = 'Imports a card';

    public function handle()
    {
        $card = Card::import($this->argument('cardmarket_product_id'));
        dump($card->toArray());

        return self::SUCCESS;
    }
}
