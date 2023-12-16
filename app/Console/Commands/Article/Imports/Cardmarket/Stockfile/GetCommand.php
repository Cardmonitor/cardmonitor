<?php

namespace App\Console\Commands\Article\Imports\Cardmarket\Stockfile;

use Illuminate\Console\Command;

class GetCommand extends Command
{
    protected $signature = 'article:imports:cardmarket:stockfile:get
        {user : The ID of the user}}';

    protected $description = 'Get Article Export from Cardmarket';

    public function handle()
    {
        $user = \App\User::findOrFail($this->argument('user'));

        $this->info('Requesting Article Export from Cardmarket for user ' . $user->name);

        $response = $user->cardmarketApi->stock_export->get();
        dump($response);
        if (is_null($response)) {
            $this->error('Error getting Article Export from Cardmarket for user ' . $user->name);
            return self::FAILURE;
        }

        $this->info('Article Export requested from Cardmarket for user ' . $user->name);

        return self::SUCCESS;
    }
}
