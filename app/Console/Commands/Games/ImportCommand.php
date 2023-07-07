<?php

namespace App\Console\Commands\Games;

use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Games\Game;
use Cardmonitor\Cardmarket\Api;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:import
        {game : ID of the game}
        {--without-singles : Just update or create the expansions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports expansions and cards from a game';

    private Api $cardmarket_api;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->cardmarket_api = App::make('CardmarketApi');

        $importable_games = $this->getGames();

        if ($importable_games->isEmpty()) {
            $this->error('No importable games found');
            return self::FAILURE;
        }

        foreach ($importable_games as $game) {
            $this->import($game);
        }

        return self::SUCCESS;
    }

    protected function import(Game $game): void
    {
        $this->info('Importing ' . $game->name);

        $cardmarket_expansions = $this->cardmarket_api->expansion->find($game->id);

        $bar = $this->output->createProgressBar(count($cardmarket_expansions['expansion']));

        foreach ($cardmarket_expansions['expansion'] as $key => $cardmarketExpansion) {
            $expansion = Expansion::createOrUpdateFromCardmarket($cardmarketExpansion);

            if ($this->option('without-singles')) {
                $bar->advance();
                continue;
            }

            try {
                $singles = $this->cardmarket_api->expansion->singles($expansion->id);
            }
            catch (\Exception $e) {
                // $this->error('Expansion ' . $cardmarketExpansion['idExpansion'] . ' not available');
                continue;
            }

            foreach ($singles['single'] as $single) {
                Card::createOrUpdateFromCardmarket($single, $expansion->id);
            }

            $bar->advance();
            usleep(50);
        }

        $bar->finish();
        $this->line('');

        $this->info('Finished');
    }

    private function getGames(): Collection
    {
        if ($this->argument('game')) {
            return Game::where('id', $this->argument('game'))->where('is_importable', true)->get();
        }

        return Game::importables();
    }
}
