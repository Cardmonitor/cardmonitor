<?php

namespace App\Console\Commands\Article\Imports\Cardmarket\Stockfile;

use Illuminate\Console\Command;

class RequestCommand extends Command
{
    protected $signature = 'article:imports:cardmarket:stockfile:request
        {user : The ID of the user}}';

    protected $description = 'Request Article Export from Cardmarket';

    public function handle()
    {
        $user = \App\User::findOrFail($this->argument('user'));

        $this->info('Requesting Article Export from Cardmarket for user ' . $user->name);

        $response = $user->cardmarketApi->stock_export->create();

        if (is_null($response)) {
            $this->error('Error requesting Article Export from Cardmarket for user ' . $user->name);
            return self::FAILURE;
        }

        $this->info('Article Export requested from Cardmarket for user ' . $user->name);

        return self::SUCCESS;
    }
}
