<?php

namespace App\Models\Articles;

use App\Collections\ArticleCollection;
use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Games\Game;
use App\Models\Localizations\Language;
use App\Models\Orders\Order;
use App\Models\Rules\Rule;
use App\Models\Storages\Storage;
use App\Transformers\Articles\Csvs\Transformer;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Article extends Model
{
    const DECIMALS = 6;

    const DEFAULT_CONDITION = 'EX';
    const DEFAULT_LANGUAGE = 1;

    const PROVISION = 0.05;
    const MIN_UNIT_PRICE = 0.02;

    const CSV_CARDMARKET_ARTICLE_ID = 0;
    const CSV_CARDMARKET_PRODUCT_ID = 1;

    const CSV_AMOUNT = [
        Game::ID_MAGIC => 14,
        Game::ID_YUGIOH => 13,
        Game::ID_POKEMON => 15,
    ];

    const BASE_PRICES = [
        'price_sell' => 'Durchschnittlicher Verkaufspreis für non-Foil',
        'price_low' => 'Niedrigster Preis für non-Foil',
        'price_trend' => 'Trend Preis für non-Foil',
        'price_german_pro' => 'Niedrigster Preis von professionellen deutschen Verkäufern',
        'price_suggested' => 'Vorgeschlagener Preis für professionelle Händler',
        'price_foil_sell' => 'Durchschnittlicher Verkaufspreis für Foil',
        'price_foil_low' => 'Niedrigster Preis (Zustand EX+) für Foil',
        'price_foil_trend' => 'Trend Preis für Foil',
        'price_low_ex' => 'Niedrigster Preis (Zustand EX+) für non-Foil',
        'price_avg_1' => 'Durchschnittlicher Verkaufspreis letzter Tag für non-Foil',
        'price_avg_7' => 'Durchschnittlicher Verkaufspreis letzte 7 Tage für non-Foil',
        'price_avg_30' => 'Durchschnittlicher Verkaufspreis letzte 30 Tage für non-Foil',
        'price_foil_avg_1' => 'Durchschnittlicher Verkaufspreis letzter Tag für Foil',
        'price_foil_avg_7' => 'Durchschnittlicher Verkaufspreis letzte 7 Tage für Foil',
        'price_foil_avg_30' => 'Durchschnittlicher Verkaufspreis letzte 30 Tage für Foil',
    ];

    const CONDITIONS = [
        'MT' => 'Mint',
        'NM' => 'Near Mint',
        'EX' => 'Excelent',
        'GD' => 'Good',
        'LP' => 'Light Played',
        'PL' => 'Played',
        'PO' => 'Poor',
    ];

    protected $appends = [
        'localName',
        'path',
        'edit_path',
        'price_rule_formatted',
        'provision_formatted',
        'state_icon',
        'state_key',
        'sync_icon',
        'unit_cost_formatted',
        'unit_price_formatted',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:' . self::DECIMALS,
        'unit_price' => 'decimal:' . self::DECIMALS,
        'provision' => 'decimal:' . self::DECIMALS,
    ];

    protected $fillable = [
        'bought_at_formatted',
        'bought_at',
        'card_id',
        'cardmarket_article_id',
        'cardmarket_comments',
        'cardmarket_last_edited',
        'condition_sort',
        'condition',
        'exported_at',
        'has_sync_error',
        'hash',
        'index',
        'is_altered',
        'is_foil',
        'is_in_shoppingcard',
        'is_playset',
        'is_signed',
        'language_id',
        'number',
        'price_rule_formatted',
        'price_rule',
        'provision_formatted',
        'provision_formatted',
        'provision',
        'rule_applied_at',
        'rule_difference_percent',
        'rule_difference',
        'rule_id',
        'should_sync',
        'slot',
        'sold_at_formatted',
        'sold_at',
        'source_id',
        'source_slug',
        'state_comments',
        'state',
        'storage_id',
        'sync_error',
        'synced_at',
        'unit_cost_formatted',
        'unit_cost',
        'unit_price_formatted',
        'unit_price',
        'user_id',
    ];

    protected $guarded = [
        'id',
        'state_icon',
        'state_key',
        'sync_icon',
    ];

    protected $dates = [
        'cardmarket_last_edited',
        'bought_at',
        'exported_at',
        'sold_at',
    ];

    /**
     * The booting method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function($model)
        {
            if (! $model->user_id) {
                $model->user_id = auth()->user()->id;
            }

            if (! $model->language_id) {
                $model->language_id = self::DEFAULT_LANGUAGE;
            }

            if (! $model->condition) {
                $model->condition = self::DEFAULT_CONDITION;
            }

            return true;
        });
    }

    public static function syncFromStockFile(int $user_id, int $game_id, string $path): array
    {
        $cardmarket_article_ids = [];
        $cardmarket_cards = [];
        $all_updated_article_ids = [];

        $expansions = Expansion::where('game_id', $game_id)->get()->keyBy('abbreviation');
        $no_storage = Storage::noStorage($user_id)->first();
        $no_storage_id = $no_storage->id ?? null;

        $row_count = 0;
        $stockfile = fopen($path, "r");
        while (($stock_row = fgetcsv($stockfile, 2000, ";")) !== FALSE) {

            if ($row_count == 0) {
                $row_count++;
                continue;
            }

            $stock_row['expansion_id'] = $expansions[$stock_row[4]]->id;
            $stock_row_id = $stock_row[self::CSV_CARDMARKET_ARTICLE_ID];
            $stock_row_ids[] = $stock_row_id;

            Card::firstOrImport($stock_row[self::CSV_CARDMARKET_PRODUCT_ID]);

            $cardmarket_article = Transformer::transform($game_id, $stock_row);

            $cardmarket_article['expansion_id'] = $stock_row['expansion_id'];

            $cardmarket_article['user_id'] = $user_id;
            $cardmarket_article['storage_id'] = $no_storage_id;
            $cardmarket_article['unit_cost'] = \App\Models\Items\Card::defaultPrice($user_id, '');
            $cardmarket_article['exported_at'] = now();

            if (! Arr::has($cardmarket_cards, $stock_row[self::CSV_CARDMARKET_PRODUCT_ID])) {
                $cardmarket_cards[$stock_row[self::CSV_CARDMARKET_PRODUCT_ID]] = [
                    'articles' => [],
                    'amount' => 0,
                ];
            }

            $cardmarket_cards[$stock_row[self::CSV_CARDMARKET_PRODUCT_ID]]['articles'][$stock_row[self::CSV_CARDMARKET_ARTICLE_ID]] = $cardmarket_article;
            $cardmarket_cards[$stock_row[self::CSV_CARDMARKET_PRODUCT_ID]]['amount'] += $stock_row[self::CSV_AMOUNT[$game_id]];
        }

        foreach ($cardmarket_cards as $cardmarket_product_id => &$cardmarket_card) {

            $articles_for_card = Article::where('user_id', $user_id)
                ->where('card_id', $cardmarket_product_id)
                ->whereNull('sold_at')
                ->get()
                ->keyBy('id');

            $available_article_ids = $articles_for_card->pluck('id')->toArray();
            $updated_article_ids = [];
            $cardmarket_articles = $cardmarket_card['articles'];

            // Alle vorhandenen Artikel synchronisieren
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                $articles = $articles_for_card->where('cardmarket_article_id', $cardmarket_article['cardmarket_article_id'])
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $article->update([
                        'cardmarket_comments' => $cardmarket_article['cardmarket_comments'],
                        'language_id' => $cardmarket_article['language_id'],
                        'condition' => Arr::get($cardmarket_article, 'condition', ''),
                        'is_foil' => Arr::get($cardmarket_article, 'is_foil', false),
                        'is_signed' => Arr::get($cardmarket_article, 'isSigned', false),
                        'is_altered' => Arr::get($cardmarket_article, 'isAltered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'isPlayset', false),
                        'unit_price' => $cardmarket_article['unit_price'],
                    ]);
                    $updated_article_ids[] = $article->id;
                    $articles_for_card->forget($article->id);
                    $cardmarket_article['amount']--;
                }

                if ($cardmarket_article['amount'] == 0) {
                    unset($cardmarket_articles[$cardmarket_article_id]);
                }
            }

            $available_article_ids = array_diff($available_article_ids, $updated_article_ids);

            // Gleiche Artikel anpassen
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                $articles = $articles_for_card->where('articles.language_id', $cardmarket_article['language_id'])
                    ->where('condition', Arr::get($cardmarket_article, 'condition', ''))
                    ->where('is_foil', Arr::get($cardmarket_article, 'is_foil', false))
                    ->where('is_signed', Arr::get($cardmarket_article, 'isSigned', false))
                    ->where('is_altered', Arr::get($cardmarket_article, 'isAltered', false))
                    ->where('is_playset', Arr::get($cardmarket_article, 'isPlayset', false))
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $article->update([
                        'cardmarket_article_id' => $cardmarket_article['cardmarket_article_id'],
                        'cardmarket_comments' => $cardmarket_article['cardmarket_comments'],
                        'language_id' => $cardmarket_article['language_id'],
                        'condition' => Arr::get($cardmarket_article, 'condition', ''),
                        'is_foil' => Arr::get($cardmarket_article, 'is_foil', false),
                        'is_signed' => Arr::get($cardmarket_article, 'isSigned', false),
                        'is_altered' => Arr::get($cardmarket_article, 'isAltered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'isPlayset', false),
                        'unit_price' => $cardmarket_article['unit_price'],
                    ]);
                    $updated_article_ids[] = $article->id;
                    $articles_for_card->forget($article->id);
                    $cardmarket_article['amount']--;
                }

                if ($cardmarket_article['amount'] == 0) {
                    unset($cardmarket_articles[$cardmarket_article_id]);
                }
            }

            $available_article_ids = array_diff($available_article_ids, $updated_article_ids);

            // Restliche Artikel anpassen
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                $articles = $articles_for_card
                    ->where('user_id', $user_id)
                    ->whereNull('sold_at')
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $article->update([
                        'cardmarket_article_id' => $cardmarket_article['cardmarket_article_id'],
                        'cardmarket_comments' => $cardmarket_article['cardmarket_comments'],
                        'language_id' => $cardmarket_article['language_id'],
                        'condition' => Arr::get($cardmarket_article, 'condition', ''),
                        'is_foil' => Arr::get($cardmarket_article, 'is_foil', false),
                        'is_signed' => Arr::get($cardmarket_article, 'isSigned', false),
                        'is_altered' => Arr::get($cardmarket_article, 'isAltered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'isPlayset', false),
                        'unit_price' => $cardmarket_article['unit_price'],
                    ]);
                    $updated_article_ids[] = $article->id;
                    $articles_for_card->forget($article->id);
                    $cardmarket_article['amount']--;
                }

                if ($cardmarket_article['amount'] == 0) {
                    unset($cardmarket_articles[$cardmarket_article_id]);
                }
            }

            $available_article_ids = array_diff($available_article_ids, $updated_article_ids);

            // Nicht vorhandene Artikel erstellen
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                if ($cardmarket_article['amount'] < 1) {
                    continue;
                }
                foreach (range(0, ($cardmarket_article['amount'] - 1)) as $index) {
                    $article = self::create([
                        'user_id' => $user_id,
                        'card_id' => $cardmarket_article['card_id'],
                        'cardmarket_article_id' => $cardmarket_article['cardmarket_article_id'],
                        'cardmarket_comments' => $cardmarket_article['cardmarket_comments'],
                        'language_id' => $cardmarket_article['language_id'],
                        'condition' => Arr::get($cardmarket_article, 'condition', ''),
                        'is_foil' => Arr::get($cardmarket_article, 'is_foil', false),
                        'is_signed' => Arr::get($cardmarket_article, 'isSigned', false),
                        'is_altered' => Arr::get($cardmarket_article, 'isAltered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'isPlayset', false),
                        'unit_price' => $cardmarket_article['unit_price'],
                        'unit_cost' => $cardmarket_article['unit_cost'],
                        'storage_id' => $cardmarket_article['storage_id'],
                        'index' => $index,
                        'has_sync_error' => false,
                        'sync_error' => null,
                        'sold_at' => null,
                    ]);
                    $updated_article_ids[] = $article->id;
                }
            }

            // Überflüssige Artikel löschen
            Article::where('user_id', $user_id)
                ->whereIn('id', $articles_for_card->pluck('id')->toArray())
                ->whereNotNull('cardmarket_article_id')
                ->delete();

            $all_updated_article_ids = array_merge($all_updated_article_ids, $updated_article_ids);
        }

        // Restliche Artikel löschen
        self::where('user_id', $user_id)
            ->join('cards', 'cards.id', '=', 'articles.card_id')
            ->where('cards.game_id', $game_id)
            ->whereNull('articles.sold_at')
            ->whereNotNull('articles.cardmarket_article_id')
            ->whereNotIn('articles.id', $all_updated_article_ids)
            ->delete();

        return $cardmarket_article_ids;
    }

    public static function reindex(int $cardmarket_article_id, int $start = 1) : int
    {
        $collection = self::where('cardmarket_article_id', $cardmarket_article_id)
            ->whereNull('sold_at')->orderBy('index', 'ASC')->get();

        $i = 0;
        foreach ($collection as $model) {
            $model->update([
                'index' => $start++,
            ]);
            $i++;
        }

        return $i;
    }

    public static function updateOrCreateFromCardmarketOrder(int $userId, array $cardmarketArticle) : self
    {
        $card = Card::firstOrImport($cardmarketArticle['idProduct']);

        $values = [
            'user_id' => $userId,
            'card_id' => $card->id,
            'language_id' => $cardmarketArticle['language']['idLanguage'],
            'cardmarket_article_id' => $cardmarketArticle['idArticle'],
            'condition' => $cardmarketArticle['condition'],
            'unit_price' => $cardmarketArticle['price'],
            'unit_cost' => \App\Models\Items\Card::defaultPrice($userId, $card->rarity),
            'sold_at' => null,
            'is_in_shoppingcard' => $cardmarketArticle['inShoppingCart'] ?? false,
            'is_foil' => $cardmarketArticle['isFoil'] ?? false,
            'is_signed' => $cardmarketArticle['isSigned'] ?? false,
            'is_altered' => $cardmarketArticle['isAltered'] ?? false,
            'is_playset' => $cardmarketArticle['isPlayset'] ?? false,
            'cardmarket_comments' => $cardmarketArticle['comments'] ?: null,
            'has_sync_error' => false,
            'sync_error' => null,
        ];

        $article = self::updateOrCreate(['cardmarket_article_id' => $cardmarketArticle['idArticle']], $values);

        return $article;
    }

    public static function updateOrCreateFromWooCommerceAPIOrder(int $user_id, array $woocommerce_order)
    {
        $bonus = ($woocommerce_order['payment_method'] == 'cod') ? 0.15 : 0;

        $storage_woocommerce = Storage::firstOrCreate([
            'user_id' => $user_id,
            'name' => 'WooCommerce',
        ]);

        $storage_order = Storage::firstOrCreate([
            'user_id' => $user_id,
            'name' => 'Bestellung #' . $woocommerce_order['id'],
            'parent_id' => $storage_woocommerce->id,
        ]);

        $article_count = 0;
        $additional_costs = 0;
        foreach ($woocommerce_order['line_items'] as $line_item) {
            if (strpos($line_item['sku'], '-') === false) {
                $additional_costs += $line_item['total'];
            }
            else {
                $article_count += $line_item['quantity'];
            }
        }
        $additional_costs *= 1 + $bonus;
        $additional_costs_per_card = $additional_costs / $article_count;

        foreach ($woocommerce_order['line_items'] as $line_item) {

            if (strpos($line_item['sku'], '-') === false) {
                continue;
            }

            $line_item['bonus'] = $bonus;
            $line_item['storage_id'] = $storage_order->id;
            $line_item['additional_costs_per_card'] = $additional_costs_per_card;
            Article::updateOrCreateFromWooCommerceApi($user_id, $line_item);
        }
    }

    public static function updateOrCreateFromWooCommerceAPI(int $user_id, array $line_item): array
    {
        [$cardmarket_product_id, $is_foil] = explode('-', $line_item['sku']);
        $card = Card::firstOrImport($cardmarket_product_id);
        $number = self::maxNumber($user_id);

        $language = Arr::first($line_item['meta_data'], function ($meta) {
            return $meta['key'] == 'sprache';
        });

        $condition = Arr::first($line_item['meta_data'], function ($meta) {
            return $meta['key'] == 'zustand';
        });

        $unit_cost = $line_item['total'] / $line_item['quantity'] * (1 + Arr::get($line_item, 'bonus', 0)) + Arr::get($line_item, 'additional_costs_per_card', 0);

        for ($index=1; $index <= $line_item['quantity']; $index++) {
            $number = self::incrementNumber($number);
            $values = [
                'user_id' => $user_id,
                'card_id' => $card->id,
                'language_id' => Language::getIdByGermanName($language['value']),
                'cardmarket_article_id' => null,
                'condition' => array_search(substr($condition['value'], 0, strrpos($condition['value'], ' ')), Article::CONDITIONS),
                'unit_price' => $unit_cost * 3,
                'unit_cost' => $unit_cost,
                'sold_at' => null,
                'is_in_shoppingcard' => $cardmarketArticle['inShoppingCart'] ?? false,
                'is_foil' => ($is_foil == 'true'),
                'is_signed' => false,
                'is_altered' => false,
                'is_playset' => false,
                'cardmarket_comments' => null,
                'has_sync_error' => false,
                'sync_error' => null,
                'number' => $number,
                'storage_id' => $line_item['storage_id'],
            ];
            $attributes = [
                'source_slug' => 'woocommerce-api',
                'source_id' => $line_item['id'],
                'index' => $index,
            ];

            $articles[] = self::updateOrCreate($attributes, $values);
        }

        return $articles;
    }

    public static function getForGroupedPicklist(int $user_id): ArticleCollection
    {
        return self::select('articles.*', DB::raw('COUNT(articles.id) AS amount_picklist'))
            ->join('article_order', 'articles.id', '=', 'article_order.article_id')
            ->join('orders', 'orders.id', '=', 'article_order.order_id')
            ->join('cards', 'cards.id', '=', 'articles.card_id')
            ->with([
                'card',
                'language',
            ])
            ->where('articles.user_id', $user_id)
            ->where('orders.state', 'paid')
            ->orderBy('cards.color_order_by', 'ASC')
            ->orderBy('cards.cmc', 'ASC')
            ->groupBy('articles.card_id')
            ->groupBy('articles.language_id')
            ->groupBy('articles.condition')
            ->get();
    }

    /**
     * Increments the aticle number
     *
     * @param string $max_number
     * @return string
     */
    public static function incrementNumber(string $max_number = ''): string
    {
        $storage_code = 'A000';
        $number = 1;

        if ($max_number) {
            [$storage_code, $number] = explode('.', $max_number);
            if ($number == 250) {
                $number = 0;
                $storage_code++;
            }
            $number++;
        }

        return $storage_code . '.' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public static function maxNumber(int $user_id): string
    {
        $article = self::where('user_id', $user_id)->orderBy('number', 'DESC')->first();

        if ($article) {
            return $article->number ?? '';
        }

        return '';
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new ArticleCollection($models);
    }

    public function isDeletable() : bool
    {
        return true;
    }

    public function toCardmarket() : array
    {
        $cardmarketArticle = [
            'idLanguage' => $this->language_id,
            'comments' => $this->cardmarket_comments,
            'count' => 1,
            'price' => number_format($this->unit_price, 2),
            'condition' => $this->condition,
            'isFoil' => $this->is_foil ? 'true' : 'false',
            'isSigned' => $this->is_signed ? 'true' : 'false',
            'isPlayset' => $this->is_playset ? 'true' : 'false',
        ];

        if ($this->cardmarket_article_id) {
            $cardmarketArticle['idArticle'] = $this->cardmarket_article_id;
        }
        else {
            $cardmarketArticle['idProduct'] = $this->card->cardmarket_product_id;
        }

        return $cardmarketArticle;
    }

    public function sync(): bool
    {
        if ($this->cardmarket_article_id) {
            return $this->syncUpdate();
        }

        return $this->syncAdd();
    }

    public function syncAdd(): bool
    {
        $response = $this->user->cardmarketApi->stock->add([$this->toCardmarket()]);
        if ($response['inserted']['success']) {
            $cardmarketArticle = $response['inserted']['idArticle'];
            $this->update([
                'cardmarket_article_id' => $cardmarketArticle['idArticle'],
                'cardmarket_last_edited' => new Carbon($cardmarketArticle['lastEdited']),
                'exported_at' => now(),
                'synced_at' => now(),
                'has_sync_error' => false,
                'sync_error' => null,
                'should_sync' => false,
            ]);

            return true;
        }

        $this->update([
            'has_sync_error' => true,
            'sync_error' => $response['inserted']['error'],
            'should_sync' => true,
            // 'cardmarket_article_id' => null,
        ]);

        return false;
    }

    public function syncUpdate(): bool
    {
        try {
            $response = $this->user->cardmarketApi->stock->update([$this->toCardmarket()]);
            if (is_array($response['updatedArticles'])) {
                $cardmarketArticle = $response['updatedArticles'];
                $this->update([
                    'cardmarket_article_id' => $cardmarketArticle['idArticle'],
                    'cardmarket_last_edited' => new Carbon($cardmarketArticle['lastEdited']),
                    'synced_at' => now(),
                    'has_sync_error' => false,
                    'sync_error' => null,
                    'should_sync' => false,
                ]);

                return true;
            }
            elseif (is_array($response['notUpdatedArticles'])) {
                $this->update([
                    'has_sync_error' => true,
                    'sync_error' => $response['notUpdatedArticles']['error'],
                    'should_sync' => true,
                    // 'cardmarket_article_id' => null,
                ]);

                return false;
            }
            else {
                // Artikel nicht vorhanden, wahrscheinlich auf Cardmarket verändert und die Cardmarket Article ID hat sich verändert -> Alle Artikel synchronisieren
                $this->update([
                    'has_sync_error' => true,
                    'sync_error' => 'Artikel nicht auf Cardmarket vorhanden. Bitte alle Artikel synchronisieren.',
                    'should_sync' => true,
                    'cardmarket_article_id' => null,
                ]);

                return false;
            }
        }
        catch (\Exception $e) {

            dump('article', $this->toCardmarket());
            dump('response', $response);

            throw $e;
        }
    }

    public function syncDelete() : bool
    {
        if (is_null($this->cardmarket_article_id)) {
            return true;
        }

        $response = $this->user->cardmarketApi->stock->delete([
            'idArticle' => $this->cardmarket_article_id,
            'count' => 1,
        ]);

        if (is_null($response)) {
            return false;
        }

        if (Arr::has($response['deleted'], 'message')) {
            $this->update([
                'cardmarket_article_id' => null
            ]);

            return true;
        }

        return $response['deleted']['success'];
    }

    public function syncAmount() : void
    {
        $this->setCardmarketArticleIdForSimilar();
        $this->refresh();

        if (! $this->cardmarket_article_id) {
            return;
        }


        $cardmarketArticle = $this->user->cardmarketApi->stock->article($this->max_cardmarket_article_id);
        if (is_null($cardmarketArticle)) {
            // search for similar product on cardmarket
            // if found -> cardmarket_article_id update
        }
        $cardmarket_article_count = $cardmarketArticle['article']['count'];

        if ($cardmarket_article_count == 0) {
            return;
        }

        Article::reindex($this->cardmarket_article_id);

        for($i = $this->amount; $i < $cardmarket_article_count; $i++) {
            $this->copy();
        }

        Article::where('cardmarket_article_id', $this->cardmarket_article_id)
            ->whereNull('sold_at')
            ->where('index', '>', $cardmarket_article_count)
            ->delete();
    }

    public function setCardmarketArticleIdForSimilar($cardmarket_article_id = null)
    {
        self::similarTo($this)->update([
            'cardmarket_article_id' => $cardmarket_article_id ?? $this->max_cardmarket_article_id,
        ]);
    }

    public function calculateProvision() : float
    {
        $this->attributes['provision'] = ceil(self::PROVISION * ($this->unit_price * 100)) / 100;

        return $this->attributes['provision'];
    }

    public function copy() : self
    {
        return self::create($this->only([
            'card_id',
            'cardmarket_article_id',
            'cardmarket_comments',
            'condition',
            'is_altered',
            'is_foil',
            'is_playset',
            'is_signed',
            'language_id',
            'storage_id',
            'slot',
            'unit_cost',
            'unit_price',
            'user_id',
        ]));
    }

    public function setAmount(int $newAmount, bool $sync = true)
    {
        if ($sync) {
            $this->syncAmount();
        }

        $difference = $newAmount - $this->amount;

        if ($difference == 0) {
            return [
                'amount' => $newAmount,
                'affected' => 0,
            ];
        }

        return ($difference > 0 ? $this->incrementAmount($difference, $sync) : $this->decrementAmount(abs($difference), $sync));
    }

    public function incrementAmount(int $amount, bool $sync = true)
    {
        $created_count = 0;
        for ($i = 0; $i < $amount; $i++) {
            $model = $this->copy();
            if ($sync) {
                $model->cardmarket_article_id = null;
                $model->syncAdd();
            }

            $created_count++;
        }

        if ($this->cardmarket_article_id) {
            self::reindex($this->cardmarket_article_id);
        }

        return [
            'amount' => $amount,
            'affected' => $created_count,
        ];
    }

    public function decrementAmount(int $amount, bool $sync = true)
    {
        $articles = self::where('user_id', $this->user_id)
            ->where('cardmarket_article_id', $this->cardmarket_article_id)
            ->whereNull('sold_at')
            ->orderBy('index', 'DESC')
            ->limit($amount)
            ->get();

        $deleted_count = 0;
        foreach ($articles as $key => $article) {
            $isDeletable = $article->isDeletable();
            if ($sync) {
                $isDeletable = $article->syncDelete();
            }

            if (! $isDeletable) {
                continue;
            }

            $article->delete();
            $deleted_count++;
        }

        self::reindex($this->cardmarket_article_id);

        return [
            'amount' => $amount,
            'affected' => $deleted_count,
        ];
    }

    public function setStorage(\App\Models\Storages\Storage $storage, int $slot = 0): self
    {
        $this->storage()->associate($storage);
        $this->slot = $slot;

        return $this;
    }

    public function unsetStorage(): self
    {
        $this->storage()->dissociate();
        $this->slot = 0;

        return $this;
    }

    public function setBoughtAtFormattedAttribute($value)
    {
        $this->attributes['bought_at'] = Carbon::createFromFormat('d.m.Y H:i', $value);
        Arr::forget($this->attributes, 'bought_at_formatted');
    }

    public function setConditionAttribute($value)
    {
        $this->attributes['condition'] = $value;
        $this->attributes['condition_sort'] = (int) array_search($value, array_keys(array_reverse(self::CONDITIONS)));
    }

    public function setSoldAtFormattedAttribute($value)
    {
        $this->attributes['sold_at'] = Carbon::createFromFormat('d.m.Y H:i', $value);
        Arr::forget($this->attributes, 'sold_at_formatted');
    }

    public function setUnitCostFormattedAttribute($value)
    {
        $this->unit_cost = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'unit_cost_formatted');
    }

    public function setUnitPriceFormattedAttribute($value)
    {
        $this->unit_price = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'unit_price_formatted');
    }

    public function setPriceRuleFormattedAttribute($value)
    {
        $this->price_rule = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'price_rule_formatted');
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = $value;

        $this->calculateProvision();
    }

    public function setProvisionFormattedAttribute($value)
    {
        $this->attributes['provision'] = number_format(str_replace(',', '.', $value), self::DECIMALS, '.', '');
        Arr::forget($this->attributes, 'provision_formatted');
    }

    public function getAmountAttribute() : int
    {
        return self::similarTo($this)->count();
    }

    public function getPositionTypeAttribute() : string
    {
        return 'Artikel';
    }

    public function getLocalNameAttribute() : string
    {
        $localization = $this->card->localizations()->where('language_id', $this->language_id)->first();

        if (is_null($localization)) {
            return $this->card->name;
        }

        return $localization->name;
    }

    public function getSkuAttribute() : string
    {
        return $this->card->sku;
    }

    public static function skuToAttributes(string $sku) : array
    {
        if (empty($sku)) {
            return [];
        }

        $parts = explode('-', $sku);
        $expansion = Expansion::getByAbbreviation($parts[1]);
        $language = Language::getByCode($parts[2]);

        $parts_count = count($parts);
        $is_foil = false;
        $is_altered = false;

        if ($parts_count == 5) {
            $is_foil = true;
            $is_altered = true;
        }
        elseif ($parts_count == 4 && $parts['3'] == 'F') {
            $is_foil = true;
        }
        elseif ($parts_count == 4 && $parts['3'] == 'A') {
            $is_altered = true;
        }

        return [
            'card_id' => $parts[0],
            'expansion_id' => $expansion->id,
            'language_id' => $language->id,
            'is_foil' => $is_foil,
            'is_altered' => $is_altered,
            'is_signed' => false,
            'is_playset' => false,
        ];
    }

    public function getMaxCardmarketArticleIdAttribute()
    {
        return self::similarTo($this)->max('cardmarket_article_id');
    }

    public function getPathAttribute()
    {
        return $this->path('show');
    }

    public function getEditPathAttribute()
    {
        return $this->path('edit');
    }

    protected function path(string $action = '') : string
    {
        return ($this->id ? route($this->baseRoute() . '.' . $action, [
            'article' => $this->id
        ]) : '');
    }

    protected function baseRoute() : string
    {
        return 'article';
    }

    public function getProvisionFormattedAttribute()
    {
        return number_format($this->provision, 2, ',', '');
    }

    public function getStateIconAttribute()
    {
        if (is_null($this->state)) {
            return 'fa-question text-info';
        }

        switch ($this->state) {
            case 0:
                return 'fa-check text-success';
                break;
            case 1:
                return 'fa-exclamation text-danger';
                break;
        }
    }

    public function getSyncIconAttribute()
    {
        if ($this->has_sync_error) {
            return 'fa-exclamation text-danger';
        }

        if (is_null($this->exported_at) && is_null($this->synced_at)) {
            return 'fa-square text-danger';
        }

        return 'fa-check text-success';
    }

    public function getStateKeyAttribute()
    {
        return $this->state ?? -1;
    }

    public function getUnitCostFormattedAttribute()
    {
        return number_format($this->unit_cost, 2, ',', '');
    }

    public function getUnitPriceFormattedAttribute()
    {
        return number_format($this->unit_price, 2, ',', '');
    }

    public function getPriceRuleFormattedAttribute()
    {
        return number_format($this->price_rule, 2, ',', '');
    }

    public function card() : BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function language() : BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function orders() : BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }

    public function rule() : BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_id');
    }

    public function storage() : BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeIsFoil(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        return $query->where('articles.is_foil', $value);
    }

    public function scopeIsSigned(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        return $query->where('articles.is_signed', $value);
    }

    public function scopeIsAltered(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        return $query->where('articles.is_altered', $value);
    }

    public function scopeIsPlayset(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        return $query->where('articles.is_playset', $value);
    }

    public function scopeCondition(Builder $query, $value, $operator = '=') : Builder
    {
        if (is_null($value) || $value == -1) {
            return $query;
        }

        return $query->where('articles.condition_sort', $operator, $value);
    }

    public function scopeExpansion(Builder $query, $value) : Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->where('cards.expansion_id', $value);
    }

    public function scopeGame(Builder $query, $value) : Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->where('cards.game_id', $value);
    }

    public function scopeLanguage(Builder $query, $value) : Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->where('articles.language_id', $value);
    }

    public function scopeFilter(Builder $query, array $filter) : Builder
    {
        if (empty($filter)) {
            return $query;
        }

        return $query->condition(Arr::get($filter, 'condition_sort'), Arr::get($filter, 'condition_operator'))
            ->expansion(Arr::get($filter, 'expansion_id'))
            ->game(Arr::get($filter, 'game_id'))
            ->rule(Arr::get($filter, 'rule_id'))
            ->isFoil(Arr::get($filter, 'is_foil'))
            ->language(Arr::get($filter, 'language_id'))
            ->rarity(Arr::get($filter, 'rarity'))
            ->unitPrice(Arr::get($filter, 'unit_price_min'), Arr::get($filter, 'unit_price_max'))
            ->unitCost(Arr::get($filter, 'unit_cost_min'), Arr::get($filter, 'unit_cost_max'))
            ->search(Arr::get($filter, 'searchtext'))
            ->sold(Arr::get($filter, 'sold'))
            ->storage(Arr::get($filter, 'storage_id'))
            ->sync(Arr::get($filter, 'sync'));
    }

    public function scopeRarity(Builder $query, $value) : Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->where('cards.rarity', $value);
    }

    public function scopeRule(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query->whereNull('articles.rule_id');
        }

        if (! $value) {
            return $query;
        }

        return $query->where('articles.rule_id', $value);
    }

    public function scopeSearch(Builder $query, $value) : Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->join('localizations', function ($join) {
            $join->on('localizations.localizationable_id', '=', 'cards.id');
            $join->where('localizations.localizationable_type', '=', Card::class);
        })
            ->where('localizations.name', 'like', '%' . $value . '%')
            ->groupBy('articles.id');
    }

    public function scopeSold(Builder $query, $value) : Builder
    {
        if ($value == 1) {
            return $query->whereHas('orders');
        }

        if ($value == 0) {
            return $query->whereDoesntHave('orders');
        }

        return $query;
    }

    public function scopeStorage(Builder $query, $value) : Builder
    {
        if (empty($value)) {
            return $query;
        }

        if ($value == -1) {
            return $query->whereNull('articles.storage_id');
        }

        return $query->where('articles.storage_id', $value);
    }

    public function scopeUnitPrice(Builder $query, $min, $max) : Builder
    {
        if ($min) {
            $query->where('articles.unit_price', '>=', str_replace(',', '.', $min));
        }

        if ($max) {
            $query->where('articles.unit_price', '<=', str_replace(',', '.', $max));
        }

        return $query;
    }

    public function scopeUnitCost(Builder $query, $min, $max) : Builder
    {
        if ($min) {
            $query->where('articles.unit_cost', '>=', str_replace(',', '.', $min));
        }

        if ($max) {
            $query->where('articles.unit_cost', '<=', str_replace(',', '.', $max));
        }

        return $query;
    }

    public function scopeSimilarTo(Builder $query, self $article) : Builder
    {
        return $query->whereNull('sold_at')
            ->where('user_id', $article->user_id)
            ->where('card_id', $article->card_id)
            ->where('language_id', $article->language_id)
            ->where('condition', $article->condition)
            ->where('is_foil', $article->is_foil)
            ->where('is_altered', $article->is_altered)
            ->where('is_signed', $article->is_signed)
            ->where('is_playset', $article->is_playset)
            ->where('unit_price', $article->unit_price)
            ->where('cardmarket_comments', $article->cardmarket_comments);
    }

    public function scopeStock(Builder $query) : Builder
    {
        return $query->select('articles.*', DB::raw('COUNT(*) AS amount'))
            ->groupBy(
                'articles.card_id',
                'articles.language_id',
                'articles.condition',
                'articles.is_foil',
                'articles.is_altered',
                'articles.is_signed',
                'articles.is_playset',
                'articles.unit_price',
                'articles.cardmarket_comments'
            );
    }

    public function scopeSync(Builder $query, $value) : Builder
    {
        if ($value == -1 || is_null($value)) {
            return $query;
        }

        return $query->where('articles.has_sync_error', $value);
    }
}
