<?php

namespace App\Console\Commands\Expansion\Imports;

use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Support\Arr;
use Cardmonitor\Cardmarket\Api;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Artisan;

class MissingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expansion:imports:missing
        {--queue : Queue the Imports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all missing expansions';

    private Api $cardmarket_api;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->cardmarket_api = App::make('CardmarketApi');
        $games = Game::where('is_importable', true)->get();
        $expansions = Expansion::get();
        $expansion_ids = $expansions->pluck('cardmarket_expansion_id')->toArray();
        $results = [
            'total' => 0,
            'existing' => 0,
            'missing' => 0,
        ];

        foreach ($games as $game) {
            $this->line('Game: ' . $game->name);
            $response = $this->cardmarket_api->expansion->find($game->id);
            $cardmarket_expansions = $response['expansion'];
            foreach ($cardmarket_expansions as $cardmarket_expansion) {
                $results['total']++;
                if (in_array($cardmarket_expansion['idExpansion'], $expansion_ids)) {
                    $results['existing']++;
                    continue;
                }
                $results['missing']++;
                $this->line('Missing expansion: ' . $cardmarket_expansion['enName'] . ' (' . $cardmarket_expansion['abbreviation'] . ')...');
                if ($this->option('queue')) {
                    $this->line('Queuing...');
                    Artisan::queue('expansion:import', [
                        'expansion' => $cardmarket_expansion['idExpansion'],
                    ]);
                }
            }

            $this->line('Total: ' . $results['total']);
            $this->line('Existing: ' . $results['existing']);
            $this->line('Missing: ' . $results['missing']);

            $results = [
                'total' => 0,
                'existing' => 0,
                'missing' => 0,
            ];
        }

        return self::SUCCESS;
    }
}
