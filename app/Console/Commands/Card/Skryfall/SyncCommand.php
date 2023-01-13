<?php

namespace App\Console\Commands\Card\Skryfall;

use App\Models\Cards\Card;
use Illuminate\Console\Command;
use Illuminate\Support\LazyCollection;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'card:skryfall:sync
        {--all : Syncs all cards from Skryfall.}
        {--take= : Syncs only the given amount of cards from Skryfall.}
        {--user= : Syncs only the cards of the given user from Skryfall.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all cards from Skryfall.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cards = $this->getCards();
        foreach ($cards as $card) {
            $this->output->write('Syncing' . "\t" . $card->cardmarket_product_id . "\t" . $card->expansion->abbreviation . "\t" . $card->name . "\t");
            $card->updateFromSkryfallByCardmarketId($card->cardmarket_product_id);
            $this->output->writeln($card->skryfall_card_id ? '✓' : '✗');

            usleep(100000); // 0.1 seconds
        }

        return self::SUCCESS;
    }

    private function getCards(): LazyCollection
    {
        $query = Card::with([
            'expansion',
        ])
        ->whereHas('expansion')
        ->orderBy('expansion_id', 'ASC')
        ->orderBy('number', 'ASC')
        ->orderBy('name', 'ASC');

        if (! $this->option('all')) {
            $query->where(function ($query) {
                return $query->whereNull('skryfall_card_id')
                    ->orWhereNull('cmc');
            });
        }

        if ($this->option('take')) {
            $query->take($this->option('take'));
        }

        if ($this->option('user')) {
            $query->whereHas('articles', function ($query) {
                return $query->where('user_id', $this->option('user'))
                    ->sold(0);
            });
        }

        return $query->cursor();
    }
}
