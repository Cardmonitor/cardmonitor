<?php

namespace App\Support\Users;

use App\Models\Apis\Api;
use App\Models\Articles\Article;
use App\Models\Expansions\Expansion;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class CardmarketApi
{
    protected $api;
    protected $cardmarketApi;
    protected $section;

    public function __construct(Api $api)
    {
        $this->setCardmarketApi($api);
    }

    public function setCardmarketApi(Api $api)
    {
        $this->api = $api;
        $this->cardmarketApi = App::make('CardmarketApi', [
            'api' => $api,
        ]);
    }

    public function __get(string $name) : self
    {
        $this->section = $name;

        return $this;
    }

    public function __call($function, $args)
    {
        $section = $this->section;
        // dump($section, $function, $args);
        try {
            return call_user_func_array([$this->cardmarketApi->$section, $function], $args);
        }
        catch (\Exception $exc) {
            // $this->refresh();
        }
    }

    public function downloadStockFile(int $user_id, int $game_id = 1) : string
    {
        $filename = $user_id . '-stock-' . $game_id . '.csv';
        $zippedFilename = $filename . '.gz';

        $data = $this->cardmarketApi->stock->csv($game_id);
        $created = Storage::disk('local')->put($zippedFilename, base64_decode($data['stock']));

        if ($created === false) {
            return '';
        }

        shell_exec('gunzip ' . storage_path('app/' . $filename));

        Storage::disk('local')->delete($zippedFilename);

        return $filename;
    }

    public function syncAllArticles(int $game_id = 1)
    {
        $user_id = $this->api->user_id;
        $filename = $this->downloadStockFile($user_id, $game_id);

        if (empty($filename)) {
            return;
        }

        $cardmarket_article_ids = Article::syncFromStockFile($user_id, $game_id, storage_path('app/' . $filename));

        Storage::disk('local')->delete($filename);
    }

    public function syncAllSellerOrders()
    {
        $states = [
            // 'bought',
            'paid',
            'sent',
            'received',
            'lost',
            'cancelled',
        ];

        foreach ($states as $state) {
            $this->syncOrders('seller', $state);
            usleep(100);
        }
    }

    public function syncOrders(string $actor, string $state) : Collection
    {
        $user_id = $this->api->user_id;
        $cardmarketOrders_count = 0;
        $orderIds = new Collection();
        $start = 1;
        do {
            $data = $this->cardmarketApi->order->find($actor, $state, $start);
            if (is_array($data)) {
                $data_count = count($data['order']);
                $cardmarketOrders_count += $data_count;
                foreach ($data['order'] as $cardmarketOrder) {
                    $order = Order::updateOrCreateFromCardmarket($user_id, $cardmarketOrder);
                    if ($state == 'paid') {
                        $orderIds->push($order->id);
                    }
                    Artisan::queue('order:sync', [
                        'user' => $order->user_id,
                        '--order' => $order->id,
                    ]);
                }
                $start += 100;
                if ($data_count < 100) {
                    $data = null;
                }
                usleep(50);
            }
        }
        while (! is_null($data));

        return $orderIds;
    }

    public function refresh()
    {
        try {
            $access = $this->cardmarketApi->access->token($this->api->accessdata['request_token']);
            $this->api->setAccessToken($request_token, $access['oauth_token'], $access['oauth_token_secret']);
            $this->setCardmarketApi($this->api);
        }
        catch (\Exception $exc) {
            $this->api->reset();
        }

    }
}