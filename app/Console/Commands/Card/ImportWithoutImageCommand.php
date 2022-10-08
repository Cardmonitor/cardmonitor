<?php

namespace App\Console\Commands\Card;

use App\Models\Cards\Card;
use Illuminate\Console\Command;

class ImportWithoutImageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'card:import-without-image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports cards without images';

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
        $cards = Card::where('image', '')->get();
        foreach($cards as $card) {
            $this->info('Importing ' . $card->name);
            Card::import($card->cardmarket_product_id);
        }
    }
}
