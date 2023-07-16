<?php

namespace App\Console\Commands\Expansion;

use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Support\Arr;
use Cardmonitor\Cardmarket\Api;
use Illuminate\Console\Command;
use App\Support\BackgroundTasks;
use App\Console\Traits\HasLogger;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expansion:import
        {expansion : The Cardmarket ID of the expansion}
        {--without-singles : Just update or create the expansions}
        {--force : Force the import of the expansion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports an expansion with all its cards from Cardmarket';

    private Api $cardmarket_api;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(BackgroundTasks $BackgroundTasks)
    {
        $expansion_id = $this->argument('expansion');
        $backgroundtask_key = 'expansion.import.' . $expansion_id;

        $this->cardmarket_api = App::make('CardmarketApi');

        $BackgroundTasks->put($backgroundtask_key, 1);

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
        $this->line('Start');

        try {
            $this->line('Getting Expansion ' . $expansion_id . ' from Cardmarket...');
            $singles = $this->cardmarket_api->expansion->singles($expansion_id);
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            report($e);
            $this->error('Expansion ' . $expansion_id . ' not available.');
            $this->error($e->getResponse()->getBody()->getContents());
            return self::FAILURE;
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (!Arr::has($singles, 'expansion')) {
            dump($expansion_id, $singles);
            $this->error('Expansion ' . $expansion_id . ' not available.');
            return self::FAILURE;
        }

        $game = Game::find($singles['expansion']['idGame']);

        if (is_null($game)) {
            $this->error('Game with ID ' . $singles['expansion']['idGame'] . ' does not exist');
            return self::FAILURE;
        }

        $this->line('Game: ' . $game->name);

        if (!$game->is_importable && !$this->option('force')) {
            $this->error('Game is not importable');
            return self::FAILURE;
        }

        $this->line('Creating expansion: ' . $singles['expansion']['enName'] . ' (' . $singles['expansion']['abbreviation'] . ')...');
        $expansion = Expansion::createOrUpdateFromCardmarket($singles['expansion']);
        $this->line('Expansion created: ' . $expansion->name . ' (' . $expansion->abbreviation . ')');

        $this->importSingles($expansion, $singles);

        $this->line('Cards imported');
        $this->info('Finished');

        return self::SUCCESS;
    }

    private function importSingles(Expansion $expansion, array $singles)
    {
        if ($this->option('without-singles')) {
            return;
        }

        $cards_count = count($singles['single']);
        $this->line('Importing ' . $cards_count . ' cards');

        $bar = $this->output->createProgressBar($cards_count);

        foreach ($singles['single'] as $single) {
            Card::createOrUpdateFromCardmarket($single, $expansion->id);
            $bar->advance();
        }

        $bar->finish();

        $this->line('');
    }
}
