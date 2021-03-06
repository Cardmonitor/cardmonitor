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

    public function downloadStockFile(int $userId, int $gameId = 1) : string
    {
        $filename = $userId . '-stock-' . $gameId . '.csv';
        $zippedFilename = $filename . '.gz';

        $data = $this->cardmarketApi->stock->csv($gameId);
        $created = Storage::disk('local')->put($zippedFilename, base64_decode($data['stock']));

        if ($created === false) {
            return '';
        }

        shell_exec('gunzip ' . storage_path('app/' . $filename));

        Storage::disk('local')->delete($zippedFilename);

        return $filename;
    }

    public function syncAllArticles(int $gameId = 1)
    {
        $userId = $this->api->user_id;
        $filename = $this->downloadStockFile($userId, $gameId);

        if (empty($filename)) {
            return;
        }

        $expansions = Expansion::where('game_id', $gameId)->get()->keyBy('abbreviation');

        $cardmarketArticleIds = [];

        $row_count = 0;
        $articlesFile = fopen(storage_path('app/' . $filename), "r");
        while (($data = fgetcsv($articlesFile, 2000, ";")) !== FALSE) {
            if ($row_count == 0) {
                $row_count++;
                continue;
            }
            $data['expansion_id'] = $expansions[$data[4]]->id;
            $amount = $data[Article::CSV_AMOUNT[$gameId]];
            $cardmarket_article_id = $data[Article::CSV_CARDMARKET_ARTICLE_ID];
            $cardmarketArticleIds[] = $cardmarket_article_id;
            for ($i = 1; $i <= $amount; $i++) {
                Article::reindex($cardmarket_article_id);
                Article::createOrUpdateFromCsv($userId, $data, $i, $gameId);
                Article::where('cardmarket_article_id', $cardmarket_article_id)
                    ->whereNull('sold_at')
                    ->where('index', '>', $amount)
                    ->delete();
            }
            $row_count++;
        }

        Article::where('user_id', $userId)
            ->join('cards', 'cards.id', '=', 'articles.card_id')
            ->where('cards.game_id', $gameId)
            ->whereNull('sold_at')
            ->whereNotNull('cardmarket_article_id')
            ->whereNotIn('cardmarket_article_id', $cardmarketArticleIds)
            ->delete();

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
        $userId = $this->api->user_id;
        $cardmarketOrders_count = 0;
        $orderIds = new Collection();
        $start = 1;
        do {
            $data = $this->cardmarketApi->order->find($actor, $state, $start);
            if (is_array($data)) {
                $data_count = count($data['order']);
                $cardmarketOrders_count += $data_count;
                foreach ($data['order'] as $cardmarketOrder) {
                    $order = Order::updateOrCreateFromCardmarket($userId, $cardmarketOrder);
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