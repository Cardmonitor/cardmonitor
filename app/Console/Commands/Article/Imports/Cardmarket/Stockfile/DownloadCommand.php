<?php

namespace App\Console\Commands\Article\Imports\Cardmarket\Stockfile;

use App\Importers\Articles\Cardmarket\Stockfile\Json;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadCommand extends Command
{
    protected $signature = 'article:imports:cardmarket:stockfile:download
        {user : The ID of the user}}';

    protected $description = 'Download Stockfile Article Export from Cardmarket';

    public function handle()
    {
        $user = \App\User::findOrFail($this->argument('user'));

        $this->info('Requesting Article Export from Cardmarket for user ' . $user->name);

        $Json = new Json($user->id);
        $file_path = $Json->download();

        $this->info('Downlaoded Article Export Stockfile from Cardmarket for user ' . $user->name);
        $this->info('File saved to ' . $file_path);

        return self::SUCCESS;
    }
}
