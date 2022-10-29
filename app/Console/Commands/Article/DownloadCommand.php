<?php

namespace App\Console\Commands\Article;

use App\User;
use App\Models\Games\Game;
use Illuminate\Console\Command;

class DownloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:download {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download of stockfile for user';

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
        $user = User::with('api')->find($this->argument('user'));

        if (is_null($user)) {
            $this->error('User not found');
            return self::FAILURE;
        }

        $this->line('Downloading stockfiles for user ' . $user->name);

        $games = Game::importables();
        foreach ($games as $game) {
            $filename = $user->cardmarketApi->downloadStockFile($user->id, $game->id);
            $this->line($game->name . ': ' . storage_path('app/' . $filename));
        }

        return self::SUCCESS;
    }
}
