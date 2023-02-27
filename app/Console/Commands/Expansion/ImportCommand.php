<?php

namespace App\Console\Commands\Expansion;

use App\Models\Cards\Card;
use App\Models\Games\Game;
use Cardmonitor\Cardmarket\Api;
use Illuminate\Console\Command;
use App\Console\Traits\HasLogger;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use App\Support\BackgroundTasks;

class ImportCommand extends Command
{
    use HasLogger;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expansion:import
        {expansion : The Cardmarket ID of the expansion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports expansions and cards from an expansion';

    /**
     * The importable Games keyed by its Id.
     *
     * @var array
     */
    protected $importableGames = [];

    /**
     * The importable Game IDs.
     *
     * @var array
     */
    protected $importableGameIds = [];

    private Api $CardmarketApi;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(BackgroundTasks $BackgroundTasks)
    {
        $expansion_id = $this->argument('expansion');
        $backgroundtask_key = 'expansion:import.' . $expansion_id;

        $BackgroundTasks->put($backgroundtask_key, 1);

        $this->CardmarketApi = App::make('CardmarketApi');
        $this->importableGames = Game::importables()->keyBy('id');
        $this->importableGameIds = array_keys($this->importableGames->toArray());

        $this->makeLogger('logs/jobs/expansion:import/' . now()->format('Y-m-d_H-i-s') . '.log');

        try {
            $result = $this->import($expansion_id);
        }
        finally {
            $BackgroundTasks->forget($backgroundtask_key);
        }

        return $result;
    }

    protected function import(int $expansion_id)
    {
        $this->info('Start');

        try {
            $this->info('Getting Expansion ' . $expansion_id . ' from Cardmarket...');
            $singles = $this->CardmarketApi->expansion->singles($expansion_id);
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->error('Expansion ' . $expansion_id . ' not available.');
            $this->error($e->getResponse()->getBody()->getContents());
            return self::FAILURE;
        }

        $game_id = $singles['expansion']['idGame'];
        $this->info('Game: ' . $this->importableGames[$game_id]->name);
        $this->info('Creating expansion: ' . $singles['expansion']['enName'] . ' (' . $singles['expansion']['abbreviation'] . ')...');
        $expansion = Expansion::createOrUpdateFromCardmarket($singles['expansion']);
        $this->info('Expansion created: ' . $expansion->name . ' (' . $expansion->abbreviation . ')');

        if (! $this->isImportable($game_id)) {
            $this->error('Game does not exist');
            return;
        }

        $cards_count = count($singles['single']);
        $this->info('Importing ' . $cards_count . ' cards');

        $bar = $this->output->createProgressBar($cards_count);

        foreach ($singles['single'] as $single) {
            $this->log->info('Importing card: ' . $single['enName']);
            Card::createOrUpdateFromCardmarket($single, $expansion->id);
            $this->log->info('Card imported: ' . $single['enName']);
            $bar->advance();
        }

        $bar->finish();

        $this->info('');
        $this->info('Cards imported');
        $this->info('Finished');

        return self::SUCCESS;
    }

    protected function isImportable(int $game_id) : bool
    {
        return in_array($game_id, $this->importableGameIds);
    }
}
