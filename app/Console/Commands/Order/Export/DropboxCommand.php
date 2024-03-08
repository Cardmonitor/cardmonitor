<?php

namespace App\Console\Commands\Order\Export;

use App\Exporters\Orders\CsvExporter;
use App\Models\Orders\Order;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;

class DropboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:export:dropbox {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports Orders to Dropbox';

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
        $userId = $this->argument('user');
        $user = User::with(['dropbox'])->find($this->argument('user'));
        if (is_null($user->dropbox)) {
            $access_token = config('services.dropbox.accesstoken');
        }
        else {
            $user->dropbox->refresh();
            $access_token = $user->dropbox->token;
        }

        $orders = Order::where('user_id', $userId)
            ->state('paid')
            ->with([
                'articles.language',
                'articles.card.expansion',
                'articles.externalIdsCardmarket',
                'articles.externalIdsWooCommerce',
                'buyer',
            ])->get();


        $this->basePath = $this->makeFilesystem($access_token);
        $filename = $this->makeDirectory('export/' . $userId . '/order') . '/orders.csv';
        CsvExporter::all($userId, $orders, $filename);
        Storage::disk('dropbox')->putFileAs($this->basePath, Storage::disk('public')->path($filename), 'orders.csv');
    }

    protected function makeFilesystem(string $access_token) : string
    {
        Storage::extend('dropbox', function ($app, $config) use ($access_token) {
            $client = new Client($access_token);

            return new Filesystem(new DropboxAdapter($client));
        });

        $path = 'orders/export';
        Storage::disk('dropbox')->makeDirectory($path);
        Storage::disk('dropbox')->delete(Storage::disk('dropbox')->allFiles($path));

        return $path;
    }

    protected function makeDirectory($path)
    {
        Storage::disk('public')->makeDirectory($path);

        return $path;
    }
}
