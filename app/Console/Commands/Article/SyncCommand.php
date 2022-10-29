<?php

namespace App\Console\Commands\Article;

use App\Models\Expansions\Expansion;
use App\Models\Games\Game;
use App\User;
use Illuminate\Console\Command;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:sync {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add/Update articles from cardmarket API';

    protected $user;

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
        $this->user = User::find($this->argument('user'));
        try {
            $this->processing();
            foreach (Game::keyValue() as $gameId => $name) {
                $this->user->cardmarketApi->syncAllArticles($gameId);
            }
            $this->processed();
        }
        catch (\Exception $e) {
            $this->processed();

            throw $e;
        }
    }

    public function processing()
    {
        $this->user->update([
            'is_syncing_articles' => true,
        ]);
    }

    public function processed()
    {
        $this->user->update([
            'is_syncing_articles' => false,
        ]);
    }
}
