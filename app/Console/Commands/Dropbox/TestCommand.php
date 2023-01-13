<?php

namespace App\Console\Commands\Dropbox;

use App\APIs\Dropbox\Dropbox;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dropbox:test {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tests the dropbox provider.';

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
        $user = User::with(['dropbox'])
            ->whereHas('dropbox')
            ->find($this->argument('user'));

        if (is_null($user)) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        $user->dropbox->ensureValidToken();

        $this->line('Listing all files for user ' . $user->name);

        $this->makeFilesystem($user->dropbox->token);
        $paths = Storage::disk('dropbox')->allFiles('');
        if (empty($paths)) {
            $this->error('No files found.');
            return self::FAILURE;
        }

        foreach ($paths as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }

    protected function makeFilesystem(string $access_token)
    {
        Storage::extend('dropbox', function ($app, $config) use ($access_token) {
            $client = new Client($access_token);

            return new Filesystem(new DropboxAdapter($client));
        });
    }
}
