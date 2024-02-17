<?php

namespace App\Models\Articles;

use App\APIs\WooCommerce\WooCommerceOrder;
use App\User;
use Carbon\Carbon;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use App\Models\Rules\Rule;
use Illuminate\Support\Arr;
use App\Models\Orders\Order;
use App\Models\Storages\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Expansions\Expansion;
use App\Collections\ArticleCollection;
use App\Enums\ExternalIds\ExternalType;
use App\Enums\Orders\Status;
use App\Models\Localizations\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Builder;
use App\Transformers\Articles\Csvs\Transformer;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'EX' => 'Excellent',
        'GD' => 'Good',
        'LP' => 'Light Played',
        'PL' => 'Played',
        'PO' => 'Poor',
    ];

    const STATE_OK = 0;
    const STATE_PROBLEM = 1;
    const STATE_ON_HOLD = 2;
    const STATE_NOT_PRESENT = 3;

    const SYNC_STATE_SUCCESS = 0;
    const SYNC_STATE_ERROR = 1;
    const SYNC_STATE_NOT_SYNCED = 2;

    protected $appends = [
        'can_upload_to_cardmarket',
        'path',
        'edit_path',
        'price_rule_formatted',
        'provision_formatted',
        'state_icon',
        'state_key',
        'sync_icon',
        'sync_icon_cardmarket',
        'sync_icon_woocommerce',
        'sync_title_cardmarket',
        'sync_title_woocommerce',
        'unit_cost_formatted',
        'unit_price_formatted',
        'should_show_card_name',
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
        'card_name',
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
        'is_reverse_holo',
        'is_first_edition',
        'is_in_shoppingcard',
        'is_playset',
        'is_signed',
        'language_id',
        'local_name',
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
        'is_sellable',
        'is_sellable_since',
        'sold_at_formatted',
        'sold_at',
        'source_id',
        'source_slug',
        'source_sort',
        'state_comments',
        'state',
        'storage_id',
        'storing_history_id',
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
        'is_sellable_since',
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

        static::creating(function(self $model)
        {
            if (! $model->user_id) {
                $model->user_id = auth()->user()->id;
            }

            if (! $model->language_id) {
                $model->language_id = self::DEFAULT_LANGUAGE;
            }

            if ($model->shouldUpdateLocalAndCardNames()) {
                $model->local_name = $model->getLocalName();
                $model->card_name = $model->card->name;
            }

            if (! $model->condition) {
                $model->condition = self::DEFAULT_CONDITION;
            }

            if (! $model->cardmarket_comments) {
                $model->cardmarket_comments = 'Your LGS based in Lübeck |';
            }

            return true;
        });

        static::updating(function (self $model) {
            if ($model->shouldUpdateLocalAndCardNames()) {
                $model->local_name = $model->getLocalName();
                $model->card_name = $model->card->name;
            }
        });

        static::deleting(function (self $model) {
            $model->externalIds()->whereNull('external_id')->delete();
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

            // Expansion not found, import it
            if (! Arr::has($expansions, $stock_row[4])) {
                $card = Card::import($stock_row[self::CSV_CARDMARKET_PRODUCT_ID]);
                $expansions = Expansion::where('game_id', $game_id)->get()->keyBy('abbreviation');

                Artisan::queue('expansion:import', ['expansion' => $card->expansion_id]);
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
                $articles = $articles_for_card
                    ->where('cardmarket_article_id', $cardmarket_article['cardmarket_article_id'])
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $article->update([
                        'cardmarket_comments' => $cardmarket_article['cardmarket_comments'],
                        'language_id' => $cardmarket_article['language_id'],
                        'condition' => Arr::get($cardmarket_article, 'condition', ''),
                        'is_foil' => Arr::get($cardmarket_article, 'is_foil', false),
                        'is_signed' => Arr::get($cardmarket_article, 'is_signed', false),
                        'is_altered' => Arr::get($cardmarket_article, 'is_altered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'is_playset', false),
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
                $articles = $articles_for_card
                    ->where('language_id', $cardmarket_article['language_id'])
                    ->where('condition', Arr::get($cardmarket_article, 'condition', ''))
                    ->where('is_foil', Arr::get($cardmarket_article, 'is_foil', false))
                    ->where('is_signed', Arr::get($cardmarket_article, 'is_signed', false))
                    ->where('is_altered', Arr::get($cardmarket_article, 'is_altered', false))
                    ->where('is_playset', Arr::get($cardmarket_article, 'is_playset', false))
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $article->update([
                        'cardmarket_article_id' => $cardmarket_article['cardmarket_article_id'],
                        'cardmarket_comments' => $cardmarket_article['cardmarket_comments'],
                        'language_id' => $cardmarket_article['language_id'],
                        'condition' => Arr::get($cardmarket_article, 'condition', ''),
                        'is_foil' => Arr::get($cardmarket_article, 'is_foil', false),
                        'is_signed' => Arr::get($cardmarket_article, 'is_signed', false),
                        'is_altered' => Arr::get($cardmarket_article, 'is_altered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'is_playset', false),
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
                        'is_signed' => Arr::get($cardmarket_article, 'is_signed', false),
                        'is_altered' => Arr::get($cardmarket_article, 'is_altered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'is_playset', false),
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
                        'is_signed' => Arr::get($cardmarket_article, 'is_signed', false),
                        'is_altered' => Arr::get($cardmarket_article, 'is_altered', false),
                        'is_playset' => Arr::get($cardmarket_article, 'is_playset', false),
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

    public static function createFromWooCommerceProduct(int $user_id, array $woocommerce_product): self
    {
        $meta_data = array_reduce($woocommerce_product['meta_data'], function ($carry, $item) {
            $carry[$item['key']] = $item['value'];
            return $carry;
        }, []);

        if (! Arr::get($meta_data, 'card_id', false)) {
            throw new \Exception('Keine Card ID vorhanden: ' . $woocommerce_product['sku']);
        }

        $card = Card::firstOrImport(Arr::get($meta_data, 'card_id'));

        $values = [
            'user_id' => $user_id,
            'card_id' => $card->id,
            'language_id' => Arr::get($meta_data, 'language_id', Language::DEFAULT_ID),
            'condition' => Arr::get($meta_data, 'condition', ''),
            'unit_price' => $woocommerce_product['price'],
            'is_foil' => Arr::get($meta_data, 'is_foil', '') === 'Ja',
            'is_reverse_holo' => Arr::get($meta_data, 'is_reverse_holo', '') === 'Ja',
            'is_first_edition' => Arr::get($meta_data, 'is_first_edition', '') === 'Ja',
            'is_signed' => Arr::get($meta_data, 'is_signed', '') === 'Ja',
            'is_altered' => Arr::get($meta_data, 'is_altered', '') === 'Ja',
            'is_playset' => Arr::get($meta_data, 'is_playset', '') === 'Ja',
            'cardmarket_comments' => Arr::get($meta_data, 'cardmarket_comments', null),
            'number' => $woocommerce_product['sku'],
            'is_sellable' => 1,
            'is_sellable_since' => now(),
        ];

        $article = self::create($values);

        $article->externalIdsWoocommerce()->updateOrCreate([
            'user_id' => $user_id,
            'external_id' => $woocommerce_product['id'],
            'external_type' => ExternalType::WOOCOMMERCE->value,
            'sync_action' => 'CREATED',
            'sync_status' => self::SYNC_STATE_ERROR,
        ]);

        return $article;
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
            ->where('orders.state', Status::PAID->value)
            ->orderBy('cards.color_order_by', 'ASC')
            ->orderBy('cards.cmc', 'ASC')
            ->groupBy('articles.card_id')
            ->groupBy('articles.language_id')
            ->groupBy('articles.condition')
            ->get();
    }

    public static function getForPicklist(int $user_id, int $order_id = 0): ArticleCollection
    {
        $query = self::select('articles.*', 'orders.id AS order_id')
            ->join('article_order', 'articles.id', '=', 'article_order.article_id')
            ->join('orders', 'orders.id', '=', 'article_order.order_id')
            ->with([
                'card',
                'language',
            ])
            ->where('articles.user_id', $user_id)
            ->where(function ($query) {
                $query->whereNull('articles.state')
                    ->orWhere('articles.state', '!=', Article::STATE_ON_HOLD);
            })
            ->where('orders.state', 'paid')
            ->orderBy('articles.number', 'ASC');

        if ($order_id > 0) {
            $query->whereHas('orders', function ($query) use ($order_id) {
                $query->where('orders.id', $order_id);
            });
        }

        return $query->get();
    }

    /**
     * Increments the aticle number
     *
     * @param string $max_number
     * @return string
     */
    public static function incrementNumber(string $max_number = ''): string
    {
        $storage_code = 'A001';
        $number = 1;

        if ($max_number) {
            [$storage_code, $number] = explode('.', $max_number);
            if ($number == 850) {
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

    public static function numberFromCardmarketComments(?string $cardmarket_comments): string
    {
        if (! $cardmarket_comments) {
            return '';
        }

        // Text ##A001.001## finden
        preg_match('/##(.*?)##/', $cardmarket_comments, $matches);

        if (isset($matches[1])) {
            return $matches[1];
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
            'price' => number_format($this->unit_price, 2, '.', ''),
            'condition' => $this->condition,
            'isFoil' => $this->is_foil ? 'true' : 'false',
            'isSigned' => $this->is_signed ? 'true' : 'false',
            'isPlayset' => $this->is_playset ? 'true' : 'false',
            'isHoloReverse' => $this->is_holo_reverse ? 'true' : 'false',
            'isFirstEd' => $this->is_first_edition ? 'true' : 'false',
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

            $this->externalIdsCardmarket()->updateOrCreate([
                'user_id' => $this->user_id,
                'external_type' => ExternalType::CARDMARKET->value,
            ], [
                'external_id' => $cardmarketArticle['idArticle'],
                'external_updated_at' => new Carbon($cardmarketArticle['lastEdited']),
                'sync_status' => self::SYNC_STATE_SUCCESS,
                'sync_message' => null,
                'exported_at' => now(),
            ]);

            return true;
        }

        $this->update([
            'has_sync_error' => true,
            'sync_error' => $response['inserted']['error'],
            'should_sync' => true,
            // 'cardmarket_article_id' => null,
        ]);

        $this->externalIdsCardmarket()->updateOrCreate([
            'user_id' => $this->user_id,
            'external_type' => ExternalType::CARDMARKET->value,
        ], [
            'sync_status' => self::SYNC_STATE_ERROR,
            'sync_message' => $response['inserted']['error'],
        ]);

        return false;
    }

    public function syncUpdate(): bool
    {
        try {
            $response = $this->user->cardmarketApi->stock->update([$this->toCardmarket()]);
            if (is_array($response['updatedArticles'])) {
                $cardmarket_article = $response['updatedArticles'];
                $this->update([
                    'cardmarket_article_id' => $cardmarket_article['idArticle'],
                    'cardmarket_last_edited' => new Carbon($cardmarket_article['lastEdited']),
                    'synced_at' => now(),
                    'has_sync_error' => false,
                    'sync_error' => null,
                    'should_sync' => false,
                ]);

                $this->externalIdsCardmarket()->updateOrCreate([
                    'user_id' => $this->user_id,
                    'external_type' => ExternalType::CARDMARKET->value,
                ], [
                    'external_id' => $cardmarket_article['idArticle'],
                    'external_updated_at' => new Carbon($cardmarket_article['lastEdited']),
                    'sync_status' => self::SYNC_STATE_SUCCESS,
                    'sync_message' => null,
                    'exported_at' => now(),
                ]);

                return true;
            }
            elseif (is_array($response['notUpdatedArticles'])) {
                // Artikel nicht valide oder schon genau so auf Cardmarket vorhanden
                $article_response = $this->user->cardmarketApi->stock->article($this->cardmarket_article_id);
                $cardmarket_article = $article_response['article'];
                $this->update([
                    'cardmarket_article_id' => $cardmarket_article['idArticle'],
                    'unit_price' => $cardmarket_article['price'],
                    'language_id' => $cardmarket_article['language']['idLanguage'],
                    'cardmarket_last_edited' => new Carbon($cardmarket_article['lastEdited']),
                    'cardmarket_comments' => $cardmarket_article['comments'],
                    'condition' => $cardmarket_article['condition'],
                    'is_foil' => Arr::get($cardmarket_article, 'isFoil', false),
                    'is_signed' => Arr::get($cardmarket_article, 'isSigned', false),
                    'is_playset' => Arr::get($cardmarket_article, 'isPlayset', false),
                    'is_reverse_holo' => Arr::get($cardmarket_article, 'isReverseHolo', false),
                    'is_first_edition' => Arr::get($cardmarket_article, 'isFirstEd', false),
                    'is_altered' => Arr::get($cardmarket_article, 'isAltered', false),
                ]);

                $this->externalIdsCardmarket()->updateOrCreate([
                    'user_id' => $this->user_id,
                    'external_type' => ExternalType::CARDMARKET->value,
                ], [
                    'external_id' => $cardmarket_article['idArticle'],
                    'external_updated_at' => new Carbon($cardmarket_article['lastEdited']),
                    'sync_status' => self::SYNC_STATE_SUCCESS,
                    'sync_message' => null,
                    'exported_at' => now(),
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

                $this->externalIdsCardmarket()->updateOrCreate([
                    'user_id' => $this->user_id,
                    'external_type' => ExternalType::CARDMARKET->value,
                ], [
                    'external_id' => null,
                    'sync_status' => self::SYNC_STATE_ERROR,
                    'sync_message' => 'Artikel nicht auf Cardmarket vorhanden.',
                ]);

                return false;
            }
        }
        catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function syncDelete() : bool
    {
        $external_id_cardmarket = $this->externalIdsCardmarket;
        if (is_null($this->cardmarket_article_id) && (is_null($external_id_cardmarket) || is_null($external_id_cardmarket->external_id))) {
            return true;
        }

        $response = $this->user->cardmarketApi->stock->delete([
            'idArticle' => $this->cardmarket_article_id,
            'count' => 1,
        ]);

        if (is_null($response)) {
            return false;
        }

        $success = Arr::get($response, 'deleted.success', false);

        // Artikel ist nicht vorhanden, kann also gelöscht werden
        if ($success === false && is_null($this->user->cardmarketApi->stock->article($this->cardmarket_article_id))) {
            $success = true;
        }

        if ($success) {
            $this->externalIdsCardmarket()->updateOrCreate([
                'user_id' => $this->user_id,
                'external_type' => ExternalType::CARDMARKET->value,
            ], [
                'external_id' => null,
                'external_updated_at' => null,
                'sync_status' => self::SYNC_STATE_SUCCESS,
                'sync_message' => null,
            ]);

            $this->update([
                'cardmarket_article_id' => null
            ]);
        }

        return $success;
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

    public function toWooCommerce(): array
    {
        return [
            'name' => $this->card->name,
            'type' => 'simple',
            'regular_price' => $this->unit_price,
            'description' => '',
            'status' => 'publish',
            'short_description' => $this->card->type_line,
            'images' => [
                [
                    'src' => 'https://cardmonitor.d15r.de/' . $this->card->image_path,
                ],
            ],
            'manage_stock' => true,
            'stock_quantity' => 1,
            'stock_status' => 'instock',
            'sku' => $this->number,
            'meta_data' => [
                [
                    'key' => 'cardmarket_comments',
                    'value' => $this->cardmarket_comments ?? '',
                ],
                [
                    'key' => 'local_name',
                    'value' => $this->local_name,
                ],
                [
                    'key' => 'game_id',
                    'value' => $this->card->game_id,
                ],
                [
                    'key' => 'card_id',
                    'value' => $this->card->id,
                ],
                [
                    'key' => 'expansion_id',
                    'value' => $this->card->expansion->id,
                ],
                [
                    'key' => 'expansion_abbreviation',
                    'value' => $this->card->expansion->abbreviation,
                ],
                [
                    'key' => 'expansion_name',
                    'value' => $this->card->expansion->name,
                ],
                [
                    'key' => 'condition',
                    'value' => $this->condition,
                ],
                [
                    'key' => 'is_altered',
                    'value' => $this->is_altered ? 'Ja' : 'Nein',
                ],
                [
                    'key' => 'is_foil',
                    'value' => $this->is_foil ? 'Ja' : 'Nein',
                ],
                [
                    'key' => 'is_playset',
                    'value' => $this->is_playset ? 'Ja' : 'Nein',
                ],
                [
                    'key' => 'is_reverse_holo',
                    'value' => $this->is_reverse_holo ? 'Ja' : 'Nein',
                ],
                [
                    'key' => 'is_signed',
                    'value' => $this->is_signed ? 'Ja' : 'Nein',
                ],
                [
                    'key' => 'is_first_edition',
                    'value' => $this->is_first_edition ? 'Ja' : 'Nein',
                ],
                [
                    'key' => 'language_id',
                    'value' => $this->language_id,
                ],
                [
                    'key' => 'language_code',
                    'value' => $this->language->code,
                ],
            ],
        ];
    }

    public function syncWooCommerce(): bool
    {
        $external_id = $this->externalIdsWooComerce;
        if (is_null($external_id) || is_null($external_id->external_id)) {
            return $this->syncWooCommerceAdd();
        }

        return $this->syncWooCommerceUpdate();
    }

    public function syncWooCommerceAdd(): bool
    {
        $response = (new WooCommerceOrder())->createProduct($this->toWooCommerce());
        if ($response->successful()) {
            $woocommerce_product = $response->json();

            $this->externalIdsWooCommerce()->updateOrCreate([
                'user_id' => $this->user_id,
                'external_type' => ExternalType::WOOCOMMERCE->value
            ], [
                'external_id' => $woocommerce_product['id'],
                'external_updated_at' => Carbon::createFromFormat('Y-m-d\TH:i:s', $woocommerce_product['date_modified']),
                'sync_status' => self::SYNC_STATE_SUCCESS,
                'sync_action' => null,
                'sync_message' => null,
                'exported_at' => now(),
            ]);

            return true;
        }
        $woocommerce_error = $response->json();

        // Produkt mit der SKU existiert bereits
        if ($woocommerce_error['code'] === 'product_invalid_sku' && Arr::get($woocommerce_error, 'data.resource_id')) {
            $this->externalIdsWooCommerce()->updateOrCreate([
                'user_id' => $this->user_id,
                'external_type' => ExternalType::WOOCOMMERCE->value
            ], [
                'external_id' => $woocommerce_error['data']['resource_id'],
                'sync_status' => self::SYNC_STATE_SUCCESS,
                'sync_message' => null,
                'sync_action' => null,
                'exported_at' => now(),
            ]);

            return true;
        }

        $this->externalIdsWooCommerce()->updateOrCreate([
            'user_id' => $this->user_id,
            'external_type' => ExternalType::WOOCOMMERCE->value
        ], [
            'sync_status' => self::SYNC_STATE_ERROR,
            'sync_message' => $woocommerce_error['data']['message'],
        ]);

        return false;
    }

    public function syncWooCommerceUpdatePrice(): bool
    {
        $response = (new WooCommerceOrder())->updateProduct($this->externalIdsWooCommerce->external_id, [
            'regular_price' => $this->unit_price,
        ]);

        if ($response->successful()) {
            return true;
        }

        return false;
    }

    public function syncWooCommerceUpdate(): bool
    {
        $response = (new WooCommerceOrder())->updateProduct($this->externalIdsWooCommerce->external_id, $this->toWooCommerce());
        if ($response->successful()) {
            $woocommerce_product = $response->json();

            $this->externalIdsWooCommerce()->updateOrCreate([
                'user_id' => $this->user_id,
                'external_type' => ExternalType::WOOCOMMERCE->value
            ], [
                'external_id' => $woocommerce_product['id'],
                'external_updated_at' => Carbon::createFromFormat('Y-m-d\TH:i:s', $woocommerce_product['date_modified']),
                'sync_status' => self::SYNC_STATE_SUCCESS,
                'sync_action' => null,
                'sync_message' => null,
                'exported_at' => now(),
            ]);

            return true;
        }

        $woocommerce_error = $response->json();

        $values = [
            'sync_status' => self::SYNC_STATE_ERROR,
            'sync_message' => Arr::get($woocommerce_error, 'data.message', 'Unbekannter Fehler'),
        ];

        // Produkt mit der ID nicht gefunden
        if (Arr::get($woocommerce_error, 'code') === 'woocommerce_rest_product_invalid_id') {
            // Produkt anhand der SKU suchen
            $response = (new WooCommerceOrder())->products($this->toWooCommerce(), [
                'sku' => $this->number,
            ]);
            if ($response->successful()) {
                $woocommerce_products = $response->json();
                $woocommerce_products_count = count($woocommerce_products);
                if ($woocommerce_products_count === 0) {
                    return $this->syncWooCommerceAdd();
                }
                if ($woocommerce_products_count === 1) {
                    $woocommerce_product = $woocommerce_products[0];
                    $this->externalIdsWooCommerce()->updateOrCreate([
                        'user_id' => $this->user_id,
                        'external_type' => ExternalType::WOOCOMMERCE->value
                    ], [
                        'external_id' => $woocommerce_product['id'],
                        'external_updated_at' => Carbon::createFromFormat('Y-m-d\TH:i:s', $woocommerce_product['date_modified']),
                        'sync_status' => self::SYNC_STATE_SUCCESS,
                        'sync_action' => null,
                        'sync_message' => null,
                        'exported_at' => now(),
                    ]);

                    return true;
                }

            }

            // Produkt nicht (mehr) vorhanden
            $values['external_id'] = null;
            $values['sync_status'] = self::SYNC_STATE_NOT_SYNCED;
            $values['sync_action'] = null;
            $values['sync_message'] = 'Ungültige ID.';
        }

        $this->externalIdsWooCommerce()->updateOrCreate([
            'user_id' => $this->user_id,
            'external_type' => ExternalType::WOOCOMMERCE->value
        ], $values);

        return false;
    }

    public function syncWooCommerceDelete(): bool
    {
        $woocommerce_product_id = $this->externalIdsWooCommerce?->external_id;
        if (is_null($woocommerce_product_id)) {
            return true;
        }

        $response = (new WooCommerceOrder())->deleteProduct($woocommerce_product_id);
        if ($response->successful()) {
            $this->externalIdsWooCommerce()->updateOrCreate([
                'user_id' => $this->user_id,
                'external_type' => ExternalType::WOOCOMMERCE->value
            ], [
                'external_id' => null,
                'external_updated_at' => null,
                'sync_status' => self::SYNC_STATE_SUCCESS,
                'sync_action' => null,
                'sync_message' => null,
            ]);

            return true;
        }

        $woocommerce_error = $response->json();

        // Produkt mit der ID nicht gefunden
        if (Arr::get($woocommerce_error, 'code') === 'woocommerce_rest_product_invalid_id') {
            // Produkt anhand der SKU suchen
            $response = (new WooCommerceOrder())->products($this->toWooCommerce(), [
                'sku' => $this->number,
            ]);
            if ($response->successful()) {
                $woocommerce_products = $response->json();
                $woocommerce__products_count = count($woocommerce_products);
                if ($woocommerce__products_count === 0) {
                    $this->externalIdsWooCommerce()->updateOrCreate([
                        'user_id' => $this->user_id,
                        'external_type' => ExternalType::WOOCOMMERCE->value
                    ], [
                        'external_id' => null,
                        'external_updated_at' => null,
                        'sync_status' => self::SYNC_STATE_SUCCESS,
                        'sync_action' => null,
                        'sync_message' => null,
                    ]);

                    return true;
                }
                if ($woocommerce__products_count === 1) {
                    $woocommerce_product = $woocommerce_products[0];
                    $this->externalIdsWooCommerce()->updateOrCreate([
                        'user_id' => $this->user_id,
                        'external_type' => ExternalType::WOOCOMMERCE->value
                    ], [
                        'external_id' => $woocommerce_product['id'],
                        'external_updated_at' => Carbon::createFromFormat('Y-m-d\TH:i:s', $woocommerce_product['date_modified']),
                        'sync_status' => self::SYNC_STATE_SUCCESS,
                        'sync_action' => null,
                        'sync_message' => null,
                        'exported_at' => now(),
                    ]);

                    return $this->syncWooCommerceDelete();
                }
            }
        }
        elseif (Arr::get($woocommerce_error, 'code') === 'woocommerce_rest_already_trashed') {
            $this->externalIdsWooCommerce()->updateOrCreate([
                'user_id' => $this->user_id,
                'external_type' => ExternalType::WOOCOMMERCE->value
            ], [
                'external_id' => null,
                'external_updated_at' => null,
                'sync_status' => self::SYNC_STATE_SUCCESS,
                'sync_action' => null,
                'sync_message' => null,
            ]);

            return true;
        }

        $this->externalIdsWooCommerce()->updateOrCreate([
            'user_id' => $this->user_id,
            'external_type' => ExternalType::WOOCOMMERCE->value
        ], [
            'sync_status' => self::SYNC_STATE_ERROR,
            'sync_message' => Arr::get($woocommerce_error, 'data.message', 'Unbekannter Fehler'),
        ]);

        return false;
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
            'is_reverse_holo',
            'is_first_edition',
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

    public function setNumberInCardmarketComments(): void
    {
        $number_from_cardmarket_comments = $this->number_from_cardmarket_comments;

        if (empty($this->number)) {
            $this->attributes['cardmarket_comments'] = trim(str_replace('  ' , ' ', preg_replace('/##.*##/', '', $this->cardmarket_comments))) ?: null;
        }
        elseif (empty($number_from_cardmarket_comments)) {
            $this->attributes['cardmarket_comments'] = trim($this->cardmarket_comments . ' ##' . $this->number . '##');
        }
        else {
            $this->attributes['cardmarket_comments'] = str_replace($number_from_cardmarket_comments, $this->number, $this->cardmarket_comments);
        }
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

    public function setIsSellableSinceAttribute($value)
    {
        $this->attributes['is_sellable_since'] = $this->fromDateTime($value);
        $this->attributes['is_sellable'] = !is_null($this->attributes['is_sellable_since']);
    }

    public function setNumberAttribute($value): void
    {
        $this->attributes['number'] = $value;
        $this->setNumberInCardmarketComments();
    }

    public function setSoldAtFormattedAttribute($value)
    {
        $this->attributes['sold_at'] = Carbon::createFromFormat('d.m.Y H:i', $value);
        $this->attributes['is_sold'] = true;
        Arr::forget($this->attributes, 'sold_at_formatted');
    }

    public function setSoldAtAttribute($value)
    {
        $this->attributes['sold_at'] = $this->fromDateTime($value);
        $this->attributes['is_sold'] = !is_null($this->attributes['sold_at']);

        if ($this->attributes['is_sold']) {
            $this->attributes['is_sellable'] = false;
        }
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

    public function getCanUploadToCardmarketAttribute(): bool
    {
        return (bool) $this->number;
    }

    public function getAmountAttribute() : int
    {
        return self::similarTo($this)->count();
    }

    public function getExplodedNumberAttribute(): array
    {
        if (empty($this->number)) {
            return [
                'section' => '',
                'number' => '',
            ];
        }

        $exploded = explode('.', $this->number);

        return [
            'section' => Arr::get($exploded, 0, ''),
            'number' => Arr::get($exploded, 1, ''),
        ];
    }

    public function getPositionTypeAttribute() : string
    {
        return 'Artikel';
    }

    /**
     * Check if the local and card names should be updated
     * It is updated if the card_id or language_id is changed
     * Or if the local_name or card_name is empty and the articles has a card
     */
    public function shouldUpdateLocalAndCardNames(): bool {
        if (is_null($this->card_id)) {
            return false;
        }

        if (!empty($this->local_name) && !empty($this->card_name) && !$this->isDirty('card_id') && !$this->isDirty('language_id')) {
            return false;
        }

        return true;
    }

    public function getLocalName(): string
    {
        if ($this->card->relationLoaded('localizations')) {
            $localization = $this->card->localizations->where('language_id', $this->language_id)->first();
            if (! is_null($localization)) {
                return $localization->name;
            }
        }

        $localization = $this->card->localizations()->where('language_id', $this->language_id)->first();

        if (is_null($localization)) {
            return $this->card->name;
        }

        return $localization->name;
    }

    public function getNumberFromCardmarketCommentsAttribute(): string
    {
        return self::numberFromCardmarketComments($this->cardmarket_comments);
    }

    public function getOrderExportNameAttribute() : string
    {
        return $this->local_name . ' - ' . $this->condition . ' - ' . $this->language_name . ($this->is_foil ? ' - Foil' : '');
    }

    public function getLanguageNameAttribute() : string
    {
        return $this->language->name;
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

    public function getShouldShowCardNameAttribute()
    {
        return $this->card_name != $this->local_name;
    }

    public function getStateIconAttribute()
    {
        if (is_null($this->state)) {
            return 'fa-question text-info';
        }

        switch ($this->state) {
            case self::STATE_OK: return 'fa-check text-success'; break;
            case self::STATE_PROBLEM: return 'fa-exclamation text-danger'; break;
            case self::STATE_ON_HOLD: return 'fa-pause text-warning'; break;
            case self::STATE_NOT_PRESENT: return 'fa-times text-danger'; break;
        }
    }

    public function getSyncIconAttribute()
    {
        if ($this->has_sync_error) {
            return 'fa-exclamation text-danger';
        }

        if (is_null($this->exported_at) && is_null($this->synced_at)) {
            return 'fa-cloud-upload-alt text-warning';
        }

        return 'fa-check text-success';
    }

    public function getSyncIconCardmarketAttribute()
    {
        if (! $this->relationLoaded('externalIdsCardmarket')) {
            return '';
        }

        return $this->getSyncIcon($this->externalIdsCardmarket);
    }

    public function getSyncIconWooCommerceAttribute()
    {
        if (! $this->relationLoaded('externalIdsCardmarket')) {
            return '';
        }

        return $this->getSyncIcon($this->externalIdsWooCommerce);
    }

    private function getSyncIcon(?ExternalId $external_id): string
    {
        if (is_null($external_id) || is_null($external_id->external_id)) {
            return 'fa-cloud-upload-alt text-info';
        }

        if ($external_id->sync_status == self::SYNC_STATE_ERROR) {
            return 'fa-exclamation text-danger';
        }

        if ($external_id->sync_message == 'Number from Cardmarket Comments is empty') {
            return 'fa-check  text-warning';
        }

        return 'fa-check text-success';
    }

    public function getSyncTitleCardmarketAttribute()
    {
        if (! $this->relationLoaded('externalIdsCardmarket')) {
            return '';
        }

        return $this->getSyncTitle($this->externalIdsCardmarket);
    }

    public function getSyncTitleWooCommerceAttribute()
    {
        if (! $this->relationLoaded('externalIdsCardmarket')) {
            return '';
        }

        return $this->getSyncTitle($this->externalIdsWooCommerce);
    }

    private function getSyncTitle(?ExternalId $external_id): string
    {
        if (is_null($external_id) || is_null($external_id->external_id)) {
            return 'Artikel nicht auf WooCommerce vorhanden.';
        }

        if ($external_id->sync_message) {
            return $external_id->sync_message;
        }

        return $external_id->sync_action ?? '';
    }

    public function getStateKeyAttribute()
    {
        return $this->state ?? -1;
    }

    public function getUnitCostFormattedAttribute()
    {
        return number_format($this->unit_cost ?? 0, 2, ',', '');
    }

    public function getUnitPriceFormattedAttribute()
    {
        return number_format($this->unit_price ?? 0, 2, ',', '');
    }

    public function getPriceRuleFormattedAttribute()
    {
        return number_format($this->price_rule ?? 0, 2, ',', '');
    }

    public function card() : BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function externalIds(): HasMany
    {
        return $this->hasMany(ExternalId::class);
    }

    public function externalIdsCardmarket(): HasOne
    {
        return $this->hasOne(ExternalId::class)->where('external_type', ExternalType::CARDMARKET->value);
    }

    public function externalIdsWooCommerce(): HasOne
    {
        return $this->hasOne(ExternalId::class)->where('external_type', ExternalType::WOOCOMMERCE->value);
    }

    public function language() : BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }

    public function rule() : BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_id');
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function storingHistory(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Articles\StoringHistory::class);
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

    public function scopeIsSellable(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        if ($value == 1) {
            $query->where('is_sellable', true);
        }
        elseif ($value == 0) {
            $query->where('is_sellable', false);
        }

        return $query;
    }

    public function scopeIsNumbered(Builder $query, $value) : Builder
    {
        if ($value == 0) {
            return $query->whereNull('articles.number');
        }

        if ($value == 1) {
            return $query->whereNotNull('articles.number');
        }

        return $query;
    }

    public function scopeIsStored(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        if ($value == 0) {
            return $query->whereNull('articles.storing_history_id');
        }

        if ($value == 1) {
            return $query->whereNotNull('articles.storing_history_id');
        }

        return $query;
    }

    public function scopeFilter(Builder $query, array $filter) : Builder
    {
        if (empty($filter)) {
            return $query;
        }

        return $query->condition(Arr::get($filter, 'condition_sort'), Arr::get($filter, 'condition_operator'))
            ->sold(Arr::get($filter, 'sold'))
            ->isSellable(Arr::get($filter, 'is_sellable'))
            ->expansion(Arr::get($filter, 'expansion_id'))
            ->game(Arr::get($filter, 'game_id'))
            ->rule(Arr::get($filter, 'rule_id'))
            ->isFoil(Arr::get($filter, 'is_foil'))
            ->language(Arr::get($filter, 'language_id'))
            ->isNumbered(Arr::get($filter, 'is_numbered'))
            ->isStored(Arr::get($filter, 'is_stored'))
            ->order(Arr::get($filter, 'order_id'))
            ->productType(Arr::get($filter, 'product_type'))
            ->rarity(Arr::get($filter, 'rarity'))
            ->unitPrice(Arr::get($filter, 'unit_price_min'), Arr::get($filter, 'unit_price_max'))
            ->unitCost(Arr::get($filter, 'unit_cost_min'), Arr::get($filter, 'unit_cost_max'))
            ->search(Arr::get($filter, 'searchtext'))
            ->storage(Arr::get($filter, 'storage_id'))
            ->sync(Arr::get($filter, 'sync'))
            ->externalIdSyncState(Arr::get($filter, 'sync_cardmarket'), ExternalType::CARDMARKET->value)
            ->externalIdSyncState(Arr::get($filter, 'sync_woocommerce'), ExternalType::WOOCOMMERCE->value)
            ->syncAction(Arr::get($filter, 'cardmarket_sync_action'), ExternalType::CARDMARKET->value)
            ->syncAction(Arr::get($filter, 'woocommerce_sync_action'), ExternalType::WOOCOMMERCE->value);
    }

    public function scopeOrder(Builder $query, $value) : Builder
    {
        if (empty($value)) {
            return $query;
        }

        $query->orderBy('articles.number', 'ASC')
            ->orderBy('articles.source_sort', 'ASC');

        return $query->whereHas('orders', function ($query) use ($value) {
            $query->where('orders.id', $value);
        });
    }

    public function scopeProductType(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        if ($value == 0) {
            return $query->whereNull('cards.expansion_id');
        }

        if ($value == 1) {
            return $query->whereNotNull('cards.expansion_id');
        }

        return $query;
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

        // Wenn der Suchbegriff eine Lagernummer ist
        if (preg_match('/([A-Z]+)\d{3}\.\d{3}/m', $value) === 1) {
            return $query->where('articles.number', $value);
        }

        return $query->where(function ($query) use ($value) {
            $query->orWhere('articles.local_name', 'like', '%' . $value . '%')
                ->orWhere('articles.card_name', 'like', '%' . $value . '%')
                ->orWhere('articles.number', 'like', '%' . $value . '%');
        })
        ->groupBy('articles.id');
    }

    public function scopeSold(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        if ($value == 1) {
            $query->where('is_sold', true);
        }
        elseif ($value == 0) {
            $query->where('is_sold', false);
        }

        return $query;
    }

    public function scopeStorage(Builder $query, $value) : Builder
    {
        if (empty($value)) {
            return $query;
        }

        $query->orderBy('articles.number', 'ASC')
            ->orderBy('articles.source_sort', 'ASC');

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
            ->where('is_reverse_holo', $article->is_reverse_holo)
            ->where('is_first_edition', $article->is_first_edition)
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

    public function scopeExternalIdSyncState(Builder $query, $value, $external_type): Builder
    {
        if ($value == -1 || is_null($value)) {
            return $query;
        }

        // Number from Cardmarket Comments is empty
        if ($value == 3) {
            return $query->whereHas('externalIdsCardmarket', function ($query) {
                $query->where('sync_message', 'Number from Cardmarket Comments is empty');
            });
        };

        if ($value == self::SYNC_STATE_NOT_SYNCED) {
            return $query->where(function ($query) use ($external_type) {
                $query->whereDoesntHave('externalIds', function ($query) use ($external_type) {
                    $query->where('external_type', $external_type);
                })
                ->orWhereHas('externalIds', function ($query) use ($external_type) {
                    $query->where('external_type', $external_type)
                        ->whereNull('external_id');
                });
            });
        }

        return $query->whereHas('externalIds', function ($query) use ($external_type, $value) {
            $query->where('external_type', $external_type)
                ->whereNotNull('external_id')
                ->where('sync_status', $value);
        });
    }

    public function scopeSync(Builder $query, $value): Builder
    {
        if ($value == -1 || is_null($value)) {
            return $query;
        }

        if ($value == 3) {
            return $query->whereHas('externalIdsCardmarket', function ($query) {
                $query->where('sync_message', 'Number from Cardmarket Comments is empty');
            });
        };

        if ($value == self::SYNC_STATE_NOT_SYNCED) {
            return $query->whereNull('articles.exported_at')
                ->whereNull('articles.synced_at')
                ->where('articles.has_sync_error', self::SYNC_STATE_SUCCESS);
        }

        if ($value == self::SYNC_STATE_SUCCESS) {
            $query->where(function ($query) {
                return $query->whereNotNull('articles.exported_at')
                    ->orWhereNotNull('articles.synced_at');
            });
        }

        return $query->where('articles.has_sync_error', $value);
    }

    public function scopeSyncAction(Builder $query, $value, $external_type): Builder
    {
        if (is_null($value)) {
            return $query;
        }

        return $query->whereHas('externalIds', function ($query) use ($value, $external_type) {
            $query->where('external_type', $external_type)
                ->where('sync_action', $value);
        });
    }


}
