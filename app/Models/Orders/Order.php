<?php

namespace App\Models\Orders;

use App\Models\Articles\Article;
use App\Models\Cards\Card;
use App\Models\Images\Image;
use App\Models\Items\Item;
use App\Models\Items\Transactions\Sale;
use App\Models\Orders\Evaluation;
use App\Models\Storages\Content;
use App\Models\Users\CardmarketUser;
use App\Support\Locale;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    const DAYS_TO_HAVE_IAMGES = 30;
    const FORCE_UPDATE_OR_CREATE = true;

    const SHIPPING_PROFITS = [
        'Standardbrief' => 0.3,
        'Standardbrief International' => 0.3,
        'Kompaktbrief' => 0.3,
        'Kompaktbrief International' => 0.3,
        'Grossbrief' => 0.5,
        'Grossbrief International' => 0.5,
    ];

    const STATE_BOUGHT = 'bought';
    const STATE_CANCELLED = 'cancelled';
    const STATE_EVALUATED = 'evaluated';
    const STATE_LOST = 'lost';
    const STATE_PAID = 'paid';
    const STATE_RECEIVED = 'received';
    const STATE_SENT = 'sent';

    const STATES = [
        self::STATE_BOUGHT => 'Unbezahlt',
        self::STATE_PAID => 'Bezahlt',
        self::STATE_SENT => 'Versandt',
        self::STATE_RECEIVED => 'Angekommen',
        self::STATE_EVALUATED => 'Bewertet',
        self::STATE_LOST => 'Nicht Angekommen',
        self::STATE_CANCELLED => 'Storniert',
    ];

    protected $appends = [
        'editPath',
        'paid_at_formatted',
        'path',
        'revenue_formatted',
    ];

    protected $dates = [
        'bought_at',
        'canceled_at',
        'paid_at',
        'received_at',
        'sent_at',
    ];

    protected $cardDefaultPrices;

    protected $guarded = [];

    public static function updateOrCreateFromCardmarket(int $user_id, array $cardmarket_order, bool $force = false) : self
    {
        $buyer = CardmarketUser::updateOrCreateFromCardmarket($cardmarket_order['buyer']);
        $seller = CardmarketUser::updateOrCreateFromCardmarket($cardmarket_order['seller']);

        $values = [
            'source_slug' => 'cardmarket',
            'source_id' => $cardmarket_order['idOrder'],
            'cardmarket_order_id' => $cardmarket_order['idOrder'],
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'shipping_method_id' => $cardmarket_order['shippingMethod']['idShippingMethod'],
            'state' => $cardmarket_order['state']['state'],
            'shippingmethod' => $cardmarket_order['shippingMethod']['name'],
            'shipping_name' => $cardmarket_order['shippingAddress']['name'],
            'shipping_extra' => $cardmarket_order['shippingAddress']['extra'],
            'shipping_street' => $cardmarket_order['shippingAddress']['street'],
            'shipping_zip' => $cardmarket_order['shippingAddress']['zip'],
            'shipping_city' => $cardmarket_order['shippingAddress']['city'],
            'shipping_country' => $cardmarket_order['shippingAddress']['country'],
            'shipment_revenue' => $cardmarket_order['shippingMethod']['price'],
            'articles_count' => $cardmarket_order['articleCount'],
            'articles_revenue' => $cardmarket_order['articleValue'],
            'revenue' => $cardmarket_order['totalValue'],
            'user_id' => $user_id,
            'bought_at' => (Arr::has($cardmarket_order['state'], 'dateBought') ? new Carbon($cardmarket_order['state']['dateBought']) : null),
            'canceled_at' => (Arr::has($cardmarket_order['state'], 'dateCanceled') ? new Carbon($cardmarket_order['state']['dateCanceled']) : null),
            'paid_at' => (Arr::has($cardmarket_order['state'], 'datePaid') ? new Carbon($cardmarket_order['state']['datePaid']) : null),
            'received_at' => (Arr::has($cardmarket_order['state'], 'dateReceived') ? new Carbon($cardmarket_order['state']['dateReceived']) : null),
            'sent_at' => (Arr::has($cardmarket_order['state'], 'dateSent') ? new Carbon($cardmarket_order['state']['dateSent']) : null),
        ];

        $order = self::updateOrCreate([
            'source_slug' => 'cardmarket',
            'source_id' => $cardmarket_order['idOrder'],
        ], $values);
        if (Arr::has($cardmarket_order, 'evaluation')) {
            $evaluation = Evaluation::updateOrCreateFromCardmarket($order->id, $cardmarket_order['evaluation']);
        }
        if ($order->wasRecentlyCreated || $force) {
            $order->findItems();
            $order->addArticlesFromCardmarket($cardmarket_order);
        }
        $order->calculateProfits()
            ->save();

        return $order;
    }

    public static function revenuePerDay(int $userId, Carbon $start, Carbon $end)
    {
        $periods = new CarbonPeriod($start, '1 days', $end);

        $categories = [];

        $article_counts = [];
        $revenues = [];
        $costs = [];
        $profits = [];

        $orders_count = 0;
        $cards_count = 0;
        $revenue_sum = 0;
        $cost_sum = 0;
        $profit_sum = 0;

        foreach ($periods as $date) {
            $key = $date->format('Y-m-d');
            $categories[$key] = $date->format('d.m.Y');
            $article_counts[$key] = 0;
            $revenues[$key] = 0;
            $costs[$key] = 0;
            $profits[$key] = 0;
        }

        $sql = "SELECT
                    DATE(orders.paid_at) AS paid_at,
                    SUM(orders.revenue) AS revenue,
                    SUM(orders.cost) AS cost,
                    SUM(orders.profit) AS profit,
                    SUM(orders.articles_count) AS articles_count
                FROM
                    orders
                WHERE
                    orders.user_id = :user_id AND
                    orders.paid_at IS NOT NULL AND
                    orders.paid_at BETWEEN :start AND :end
                GROUP BY
                    DATE(paid_at)";
        $params = [
            'user_id' => $userId,
            'start' => $start,
            'end' => $end,
        ];
        $orders = DB::select($sql, $params);
        foreach ($orders as $key => $order) {
            $key = $order->paid_at;
            $article_counts[$key] = (float) $order->articles_count;
            $revenues[$key] = (float) $order->revenue;
            $costs[$key] = (float) $order->cost;
            $profits[$key] = (float) $order->profit;

            $orders_count++;
            $cards_count += $order->articles_count;
            $revenue_sum += $order->revenue;
            $cost_sum += $order->cost;
            $profit_sum += $order->profit;
        }

        return [
            'categories' => array_values($categories),
            'series' => [
                [
                    'name' => __('app.profit'),
                    'data' => array_values($profits),
                    'color' => '#28a745',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => __('app.costs'),
                    'data' => array_values($costs),
                    'color' => '#dc3545',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => __('app.cards'),
                    'data' => array_values($article_counts),
                    'type' => 'spline',
                    'tooltip' => [
                        'headerFormat' => '<b>{point.key}</b><br/>',
                        'pointFormat' => '{point.y:0f} Karten'
                    ],
                    'yAxis' => 1,
                ],
            ],
            'title' => [
                'text' => __('order.home.month.chart.title', ['month' => $start->monthName]),
            ],
            'month_name' => $start->monthName,
            'statistics' => [
                'cards_count' => $cards_count,
                'cost_sum' => $cost_sum,
                'orders_count' => $orders_count,
                'profit_sum' => $profit_sum,
                'revenue_sum' => $revenue_sum,
                'periods_count' => count($periods),
            ],
        ];
    }

    public static function revenuePerMonth(int $userId, int $year)
    {
        if ($year == 0) {
            $start = now()->sub('11', 'months')->startOf('month');
            $end = now()->endOf('month');
        }
        else {
            $start = new Carbon($year . '-01-01 00:00:00');
            $end = new Carbon($year . '-12-31 23:59:59');
        }
        $periods = new CarbonPeriod($start, '1 months', $end);

        $categories = [];

        $article_counts = [];
        $revenues = [];
        $costs = [];
        $profits = [];

        $orders_count = 0;
        $cards_count = 0;
        $revenue_sum = 0;
        $cost_sum = 0;
        $profit_sum = 0;

        foreach ($periods as $date) {
            $key = $date->format('Y-n');
            $categories[$key] = $date->monthName . ' ' . $date->year;
            $article_counts[$key] = 0;
            $revenues[$key] = 0;
            $costs[$key] = 0;
            $profits[$key] = 0;
        }

        $sql = "SELECT
                    YEAR(orders.paid_at) AS year,
                    MONTH(orders.paid_at) AS month,
                    SUM(orders.revenue) AS revenue,
                    SUM(orders.cost) AS cost,
                    SUM(orders.profit) AS profit,
                    SUM(orders.articles_count) AS articles_count,
                    COUNT(*) AS orders_count
                FROM
                    orders
                WHERE
                    orders.user_id = :user_id AND
                    orders.paid_at IS NOT NULL AND
                    orders.paid_at BETWEEN :start AND :end
                GROUP BY
                    year,
                    month";
        $params = [
            'user_id' => $userId,
            'start' => $start,
            'end' => $end,
        ];
        $orders = DB::select($sql, $params);
        foreach ($orders as $key => $order) {
            $key = $order->year . '-' . $order->month;
            $article_counts[$key] = (float) $order->articles_count;
            $revenues[$key] = (float) $order->revenue;
            $costs[$key] = (float) $order->cost;
            $profits[$key] = (float) $order->profit;

            $cards_count += $order->articles_count;
            $cost_sum += $order->cost;
            $orders_count += $order->orders_count;
            $profit_sum += $order->profit;
            $revenue_sum += $order->revenue;
        }

        return [
            'categories' => array_reverse(array_values($categories)),
            'series' => [
                [
                    'name' => __('app.profit'),
                    'data' => array_reverse(array_values($profits)),
                    'color' => '#28a745',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => __('app.costs'),
                    'data' => array_reverse(array_values($costs)),
                    'color' => '#dc3545',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => __('app.cards'),
                    'data' => array_reverse(array_values($article_counts)),
                    'type' => 'spline',
                    'tooltip' => [
                        'headerFormat' => '<b>{point.key}</b><br/>',
                        'pointFormat' => '{point.y:0f} Karten'
                    ],
                    'yAxis' => 1,
                ],
            ],
            'title' => [
                'text' => $year == 0 ? __('order.home.year.chart.title_latest') : __('order.home.year.chart.title', ['year' => $year]),
            ],
            'month_name' => $start->monthName,
            'statistics' => [
                'cards_count' => $cards_count,
                'cost_sum' => $cost_sum,
                'orders_count' => $orders_count,
                'profit_sum' => $profit_sum,
                'revenue_sum' => $revenue_sum,
                'periods_count' => count($periods),
            ],
        ];
    }

    public static function getForPicklist(int $user_id): Collection
    {
        return self::where('user_id', $user_id)
            ->where('orders.state', ORDER::STATE_PAID)
            ->orderBy('paid_at', 'ASC')
            ->get()
            ->keyBy('id');
    }

    public function findItems()
    {
        $items = Item::where('user_id', $this->user_id)->get();

        foreach ($items as $key => $item) {
            $quantity = $item->quantity($this);
            if ($quantity == 0) {
                continue;
            }

            $this->sales()->firstOrCreate([
                'item_id' => $item->id,
                'type' => Sale::class,
                'user_id' => $this->user_id,
            ],
            [
                'quantity' => $quantity,
                'unit_cost' => $item->unit_cost,
                'at' => now(),
            ]);
        }
    }

    protected function getCardDefaultPrices() : Collection
    {
        if (! is_null($this->cardDefaultPrices)) {
            return $this->cardDefaultPrices;
        }

        $this->cardDefaultPrices = \App\Models\Items\Card::where('user_id', $this->user_id)->get()->mapWithKeys(function ($item) {
            return [$item['name'] => $item['unit_cost']];
        });

        return $this->cardDefaultPrices;
    }

    public function addArticlesFromCardmarket(array $cardmarket_order)
    {
        $this->getCardDefaultPrices();

        $article_ids = [];
        foreach ($cardmarket_order['article'] as $cardmarketArticle) {
            Card::import($cardmarketArticle['idProduct']);

            $article_ids = array_merge($article_ids, $this->addArticleFromCardmarket($cardmarketArticle));
        }

        // Überflüssige Artikel entknüpfen
        $this->articles()->sync($article_ids);
    }

    protected function addArticleFromCardmarket(array $cardmarket_article): array
    {
        $articles_left_count = $cardmarket_article['count'];
        $article_ids = [];

        if (! is_null($cardmarket_article['idArticle'])) {
            $articles = $this->articles()
                ->where('cardmarket_article_id', $cardmarket_article['idArticle'])
                ->where('user_id', $this->user_id)
                ->limit($articles_left_count)
                ->get();
            foreach ($articles as $article) {
                $article->update([
                    'sold_at' => $this->paid_at ?? $this->bought_at,
                    'unit_price' => $cardmarket_article['price'],
                ]);
                $article_ids[] = $article->id;
            }
            $articles_count = count($articles);
            $articles_left_count -= $articles_count;
            if ($articles_left_count == 0) {
                return $article_ids;
            }
        }

        // Artikel anhand von Lagernummer aus Kommentar finden
        $number_from_cardmarket_comments = Article::numberFromCardmarketComments($cardmarket_article['comments']);
        if ($number_from_cardmarket_comments) {
            $articles = Article::where('articles.user_id', $this->user_id)
                ->sold(0)
                ->where('articles.number', $number_from_cardmarket_comments)
                ->limit(1)
                ->get();
            foreach ($articles as $article) {
                $this->articles()->syncWithoutDetaching([$article->id]);
                $article->update([
                    'sold_at' => $this->paid_at ?? $this->bought_at,
                    'cardmarket_article_id' => $cardmarket_article['idArticle'],
                    'unit_price' => $cardmarket_article['price'],
                ]);
                $article_ids[] = $article->id;
            }
            $articles_count = count($articles);
            $articles_left_count -= $articles_count;
            if ($articles_left_count == 0) {
                return $article_ids;
            }
        }

        if (! is_null($cardmarket_article['idArticle'])) {
            // Article mit cardmarket_article_id
            $articles = Article::where('articles.user_id', $this->user_id)
                ->whereNull('sold_at')
                ->where('articles.cardmarket_article_id', $cardmarket_article['idArticle'])
                ->where('articles.language_id', $cardmarket_article['language']['idLanguage'])
                ->where('articles.condition', Arr::get($cardmarket_article, 'condition', ''))
                ->where('is_foil', $cardmarket_article['isFoil'] ?? false)
                ->where('is_signed', $cardmarket_article['isSigned'] ?? false)
                ->where('is_altered', $cardmarket_article['isAltered'] ?? false)
                ->where('is_playset', $cardmarket_article['isPlayset'] ?? false)
                ->limit($articles_left_count)
                ->get();
            foreach ($articles as $key => $article) {
                $this->articles()->syncWithoutDetaching([$article->id]);
                $article->update([
                    'sold_at' => $this->paid_at ?? $this->bought_at,
                    'unit_price' => $cardmarket_article['price'],
                ]);
                $article_ids[] = $article->id;
            }
            $articles_count = count($articles);
            $articles_left_count -= $articles_count;
            if ($articles_left_count == 0) {
                return $article_ids;
            }
        }

        $articles = Article::select('articles.*')
            ->join('cards', 'cards.id', '=', 'articles.card_id')
            ->where('articles.user_id', $this->user_id)
            ->whereNull('articles.sold_at')
            ->where('cards.cardmarket_product_id', $cardmarket_article['idProduct'])
            ->where('articles.language_id', $cardmarket_article['language']['idLanguage'])
            ->where('articles.condition', Arr::get($cardmarket_article, 'condition', ''))
            ->where('is_foil', $cardmarket_article['isFoil'] ?? false)
            ->where('is_signed', $cardmarket_article['isSigned'] ?? false)
            ->where('is_altered', $cardmarket_article['isAltered'] ?? false)
            ->where('is_playset', $cardmarket_article['isPlayset'] ?? false)
            ->limit($articles_left_count)
            ->get();
        foreach ($articles as $key => $article) {
            $this->articles()->syncWithoutDetaching([$article->id]);
            $article->update([
                'sold_at' => $this->paid_at ?? $this->bought_at,
                'cardmarket_article_id' => $cardmarket_article['idArticle'],
                'unit_price' => $cardmarket_article['price'],
            ]);
            $article_ids[] = $article->id;
        }
        $articles_count = count($articles);
        $articles_left_count -= $articles_count;
        if ($articles_left_count == 0) {
            return $article_ids;
        }

        $card = Card::firstOrImport($cardmarket_article['idProduct']);

        $attributes = [
            'user_id' => $this->user_id,
            'card_id' => $card->id,
            'language_id' => $cardmarket_article['language']['idLanguage'],
            'cardmarket_article_id' => $cardmarket_article['idArticle'],
            'storage_id' => (is_null($card->expansion_id) ? null : Content::defaultStorage($this->user_id, $card->expansion_id)),
            'condition' => Arr::get($cardmarket_article, 'condition', ''),
            'unit_price' => $cardmarket_article['price'],
            'unit_cost' => Arr::get($this->cardDefaultPrices, ($cardmarket_article['product']['rarity'] ?? ''), 0.02),
            'sold_at' => $this->paid_at ?? $this->bought_at, // "2019-08-30T10:59:53+0200"
            'is_in_shoppingcard' => $cardmarket_article['inShoppingCart'] ?? false,
            'is_foil' => $cardmarket_article['isFoil'] ?? false,
            'is_signed' => $cardmarket_article['isSigned'] ?? false,
            'is_altered' => $cardmarket_article['isAltered'] ?? false,
            'is_playset' => $cardmarket_article['isPlayset'] ?? false,
            'cardmarket_comments' => $cardmarket_article['comments'] ?: null,
        ];
        foreach (range($articles_count, ($articles_left_count - 1)) as $value) {
            $article = $this->articles()->create($attributes);
            $article_ids[] = $article->id;
        }

        return $article_ids;
    }

    public function calculateProfits() : self
    {
        $provision = $this->calculateProvision();
        $itemsCost = $this->calculateItemsCost();
        $articlesProfit = $this->calculateArticlesProfit();
        $shipmentProfit = $this->calculateShipmentProfit();

        $this->attributes['cost'] = $provision + $itemsCost + $this->articles_cost + $this->shipment_cost;
        $this->attributes['profit'] = $this->revenue - $this->attributes['cost'];

        return $this;
    }

    protected function calculateProvision() : float
    {
        $this->attributes['provision'] = $this->articles->sum('provision');

        return $this->attributes['provision'];
    }

    protected function calculateItemsCost() : float
    {
        $this->attributes['items_cost'] = $this->sales->sum( function ($sale) {
            return ($sale->quantity * $sale->unit_cost);
        });

        return $this->attributes['items_cost'];
    }

    protected function calculateArticlesProfit() : float
    {
        $this->attributes['articles_cost'] = $this->articles->sum('unit_cost');
        $this->attributes['articles_profit'] = ($this->attributes['articles_revenue'] - $this->attributes['articles_cost'] - $this->provision - $this->items_cost);

        return $this->attributes['articles_profit'];
    }

    protected function calculateShipmentProfit() : float
    {
        $this->attributes['shipment_profit'] = Arr::get(self::SHIPPING_PROFITS, $this->attributes['shippingmethod'], 0.3);
        $this->attributes['shipment_cost'] = $this->attributes['shipment_revenue'] - $this->attributes['shipment_profit'];

        return $this->attributes['shipment_profit'];
    }

    public function updateFromCardmarket(array $cardmarketOrder)
    {
        $this->update([

        ]);
    }

    public function isPresale() : bool
    {
        foreach ($this->articles as $key => $article) {

            if (is_null($article->card->expansion_id)) {
                continue;
            }

            if ($article->card->expansion->isPresale()) {
                return true;
            }
        }

        return false;
    }

    public function canHaveImages(Carbon $date = null)
    {
        if (is_null($this->received_at)) {
            return true;
        }

        return ($this->received_at->diffInDays($date ?? now()) <= self::DAYS_TO_HAVE_IAMGES);
    }

    public function getShippingCountryAttribute() : string
    {
        return Locale::iso3166($this->attributes['shipping_country']);
    }

    public function getShippingCountryNameAttribute() : string
    {
        return Arr::get(config('app.iso3166_names'), $this->shipping_country, $this->shipping_country);
    }

    public function getRevenueFormattedAttribute()
    {
        return number_format($this->revenue, 2, ',', '');
    }

    public function getPreparedMessageAttribute() : string
    {
        $message = $this->user->prepared_message;

        $articlesWithStateComments = $this->articles()->with(['card.localizations'])->whereNotNull('state_comments')->get();

        $images_count = count($this->images);
        $problems_count = count($articlesWithStateComments);

        $text_images = '';
        if ($images_count) {
            $text_images = "Hier ist schon mal ein " . ($images_count == 1 ? 'Bild' : 'Bilder') ." deiner " . ($this->articles_count == 1 ? 'Karte' : 'Karten') . "\n";
            $text_images .= url($this->path . '/images');

            if ($problems_count == 0) {
                $text_images .= "\n\n";
            }
        }

        $text_comments = '';
        if ($problems_count > 0) {
            if ($images_count) {
                $text_comments .= "\n";
            }
            $text_comments .= "\nFolgendes ist mir aufgefallen:\n";
            foreach ($articlesWithStateComments as $key => $article) {
                $text_comments .= $article->localName . " " . $article->state_comments . "\n";
            }
            $text_comments .= "\n";
        }

        $search = [
            "#BUYER_FIRSTNAME#",
            "#PROBLEMS#\r\n\r\n",
            "#IMAGES#\r\n\r\n",
            "#SELLER_FIRSTNAME#",
        ];

        $replace = [
            $this->buyer->firstname,
            $text_comments,
            $text_images,
            $this->seller->firstname,
        ];

        $message = str_replace($search, $replace, $message);

        return $message;
    }

    public function getMkmNameAttribute() : string
    {
        return $this->mkm . $this->id;
    }

    public function getMkmAttribute() : string
    {
        return 'MKM';
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
        return route($this->baseRoute() . '.' . $action, ['order' => $this->id]);
    }

    protected function baseRoute() : string
    {
        return 'order';
    }

    public function getPaidAtFormattedAttribute() : string
    {
        return (is_null($this->paid_at) ? '' : $this->paid_at->format('d.m.Y H:i'));
    }

    public function getShippingAddressTextAttribute(): string
    {
        return $this->shipping_name . "\n" . ($this->shipping_extra ? $this->shipping_extra . "\n" : '') . $this->shipping_street . "\n" . $this->shipping_zip . ' ' . $this->shipping_city . "\n" . $this->shipping_country;
    }

    public function getStateFormattedAttribute(): string
    {
        return Arr::get(self::STATES, $this->state, '');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->with([
            'card.localizations',
            'card.expansion',
        ])
        ->withTimestamps();
    }

    public function articlesOnHold(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->with([
            'card.localizations',
            'card.expansion',
        ])
        ->withTimestamps()
        ->where('state', Article::STATE_ON_HOLD);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(CardmarketUser::class, 'buyer_id');
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function sales() : HasMany
    {
        return $this->hasMany(Sale::class, 'order_id');
    }

    public function seller() : BelongsTo
    {
        return $this->belongsTo(CardmarketUser::class, 'seller_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePresale(Builder $query, $value) : Builder
    {
        if (is_null($value)) {
            return $query;
        }

        return $query->whereRaw('(
            SELECT
                SUM(IF(expansions.released_at > DATE_ADD(NOW(), INTERVAL 1 DAY), 1, 0)) AS is_presale
            FROM
                article_order
                    LEFT JOIN articles ON (articles.id = article_order.article_id)
                    LEFT JOIN cards ON (cards.id = articles.card_id)
                    LEFT JOIN expansions ON (expansions.id = cards.expansion_id)
            WHERE
                article_order.order_id = orders.id
            GROUP BY
                article_order.order_id
        ) ' . ($value == 1 ? '>' : '=') . ' 0');
    }

    public function scopeSearch(Builder $query, $value) : Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->join('cardmarket_users', function ($join) {
            $join->on('orders.buyer_id', '=', 'cardmarket_users.id');
        })->where( function ($query) use ($value) {
            return $query->where('orders.cardmarket_order_id', 'like', '%' . $value . '%')
                ->orWhere('cardmarket_users.name', 'like', '%' . $value . '%');
        })->groupBy('cardmarket_order_id');
    }

    public function scopeState(Builder $query, $value): Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->where('state', $value);
    }

    public function scopeCancelled(Builder $query, $value): Builder
    {
        if (is_null($value)) {
            return $query;
        }

        if ($value == 1) {
            $query->where('state', self::STATE_CANCELLED);
        }
        elseif ($value == 0) {
            $query->where('state', '!=', self::STATE_CANCELLED);
        }

        return $query;
    }

}
