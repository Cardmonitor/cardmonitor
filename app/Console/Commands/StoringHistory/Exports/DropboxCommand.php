<?php

namespace App\Console\Commands\StoringHistory\Exports;

use App\APIs\Dropbox\Dropbox;
use Illuminate\Console\Command;
use App\Models\Articles\StoringHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class DropboxCommand extends Command
{
    const MAX_ARTICLES_PER_FILE = 100;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storing-history:exports:pdf {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports storing history to Dropbox';

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
        $storing_history = StoringHistory::with([
            'user' => function ($query) {
                $query->whereHas('dropbox')->with('dropbox');
            },
        ])->find($this->argument('id'));

        if (is_null($storing_history)) {
            $this->error('Storing History not found.');
            return self::FAILURE;
        }

        $user = $storing_history->user;
        $user->dropbox->ensureValidToken();

        $this->line('Starting export for Storing History ' . $storing_history->id . ' for user ' . $user->name);

        $dropbox_path = Dropbox::makeFilesystem($user->dropbox->token, 'einlagerungen/' . $storing_history->id);

        $path = $this->makeDirectory($storing_history->id);

        $file_counter = 1;
        $storing_history->articles()
            ->with([
                'card.expansion',
                'card.localizations',
                'language',
                'orders',
            ])
            ->orderBy('articles.number', 'ASC')
            ->chunk(self::MAX_ARTICLES_PER_FILE, function ($articles) use ($storing_history, &$file_counter, $dropbox_path, $path) {
                $file_name = $storing_history->id . '-'. $file_counter .'.pdf';
                $file_path = $path . $file_name;

                StoringHistory::getPDF($articles, true)->save($file_path);

                Storage::disk('dropbox')->putFileAs($dropbox_path, $file_path, $file_name);
                $this->line('Exported file ' . $file_name . ' to Dropbox');
                unlink($file_path);
                $file_counter++;
        });

        rmdir($path);

        return self::SUCCESS;
    }

    protected function makeDirectory(int $storing_history_id)
    {
        $path = storage_path('app/storing_histories/' . $storing_history_id . '/');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}
