<?php

namespace App\Console\Commands\Expansion;

use App\User;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Support\Arr;
use Cardmonitor\Cardmarket\Api;
use Illuminate\Console\Command;
use App\Support\BackgroundTasks;
use App\Notifications\FlashMessage;
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
        {user=0 : The user who started the import}
        {--without-singles : Just update or create the expansions}
        {--force : Force the import of the expansion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports an expansion with all its cards from Cardmarket';

    private Api $cardmarket_api;
    private Expansion $expansion;

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
        $result = self::FAILURE;

        try {
            $result = $this->import($expansion_id);
        }
        finally {
            $BackgroundTasks->forget($backgroundtask_key);
            $this->notifyUser($result);
        }

        return $result;
    }

    private function notifyUser(int $result): void
    {
        $user_id = $this->argument('user');
        if (empty($user_id)) {
            return;
        }

        $user = User::find($this->argument('user'));

        $data = [
            'background_tasks' => App::make(BackgroundTasks::class)->all(),
        ];

        if ($result === self::SUCCESS) {
            $user->notify(FlashMessage::success('Erweiterung ' . $this->expansion->name . ' (' . $this->expansion->abbreviation . ') importiert.', $data));
        }
        else {
            $user->notify(FlashMessage::danger('Erweiterung ' . $this->expansion->name . ' (' . $this->expansion->abbreviation . ') konnte nicht importiert werden.', $data));
        }
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
        $this->expansion = Expansion::createOrUpdateFromCardmarket($singles['expansion']);
        $this->line('Expansion created: ' . $this->expansion->name . ' (' . $this->expansion->abbreviation . ')');

        $this->importSingles($this->expansion, $singles);

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
