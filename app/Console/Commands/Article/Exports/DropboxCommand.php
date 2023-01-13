<?php

namespace App\Console\Commands\Article\Exports;

use App\User;
use Spatie\Dropbox\Client;
use Illuminate\Console\Command;
use App\Models\Articles\Article;
use League\Flysystem\Filesystem;
use App\Exporters\Articles\CsvExporter;
use Illuminate\Support\Facades\Storage;
use Spatie\FlysystemDropbox\DropboxAdapter;

class DropboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:exports:dropbox {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports articles to Dropbox';

    protected string $base_path;

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

        $this->line('Starting export for user ' . $user->name);

        $articles = Article::where('user_id', $user->id)
            ->sold(0)
            ->with([
                'card.expansion',
            ])
            ->orderBy('articles.number', 'ASC')
            ->cursor();

        $this->base_path = $this->makeFilesystem($user->dropbox->token);
        $filename = 'articles-' . now() . '.csv';
        $path = $this->makeDirectory('export/' . $user->id . '/articles') . '/' . $filename;

        CsvExporter::all($articles, $path);

        Storage::disk('dropbox')->putFileAs($this->base_path, Storage::disk('public')->path($path), $filename);

        $this->info($articles->count() . ' articles exported to Dropbox.');
        $this->line('Export finished (' . $filename . ').');

        return self::SUCCESS;
    }

    protected function makeFilesystem(string $access_token) : string
    {
        Storage::extend('dropbox', function ($app, $config) use ($access_token) {
            $client = new Client($access_token);

            return new Filesystem(new DropboxAdapter($client));
        });

        $path = 'articles/backups';
        Storage::disk('dropbox')->makeDirectory($path);

        return $path;
    }

    protected function makeDirectory($path)
    {
        Storage::disk('public')->makeDirectory($path);

        return $path;
    }
}
