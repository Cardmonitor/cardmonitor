<?php

namespace App\Console\Commands\Games;

use App\Models\Games\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all games from Cardmarket';

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
        $cardmarket_games = App::make('CardmarketApi')->games->get();
        foreach ($cardmarket_games['game'] as $key => $cardmarket_game) {
            $game = Game::updateOrCreate(['id' => $cardmarket_game['idGame']], [
                'name' => $cardmarket_game['name'],
                'abbreviation' => $cardmarket_game['abbreviation'],
            ]);

            if ($game->wasRecentlyCreated) {
                $this->info('Created game: ' . $game->name);
            }
            else {
                $this->line('Updated game: ' . $game->name . ' (' . $game->id . ')');
            }
        }
    }
}
