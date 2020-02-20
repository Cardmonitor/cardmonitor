<?php

namespace App\Console\Commands\Expansion;

use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Games\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expansion:import {expansion}';

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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->cardmarketApi = App::make('CardmarketApi');
        $this->importableGames = Game::importables()->keyBy('id');
        $this->importableGameIds = array_keys($this->importableGames->toArray());
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $expansionId = $this->argument('expansion');
        $this->import($expansionId);
    }

    protected function import(int $expansionId)
    {
        $this->info('Start');
        try {
            $singles = $this->cardmarketApi->expansion->singles($expansionId);
        }
        catch (\Exception $e) {
            $this->error('Expansion ' . $expansionId . ' not available');
            return;
        }
        $gameId = $singles['expansion']['idGame'];
        $expansion = Expansion::createOrUpdateFromCardmarket($singles['expansion']);

        if (! $this->isImportable($gameId)) {
            $this->error('Game does not exist');
            return;
        }

        $this->info('Importing Expansion ' . $expansion->name);

        $bar = $this->output->createProgressBar(count($singles['single']));

        foreach ($singles['single'] as $key => $single) {
            Card::createOrUpdateFromCardmarket($single, $expansion->id);
            $bar->advance();
        }

        $bar->finish();

        $this->info('');
        $this->info('Finished');
    }

    protected function isImportable(int $gameId) : bool
    {
        return in_array($gameId, $this->importableGameIds);
    }
}
