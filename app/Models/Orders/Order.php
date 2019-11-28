<?php

namespace App\Models\Orders;

use App\Models\Articles\Article;
use App\Models\Cards\Card;
use App\Models\Images\Image;
use App\Models\Items\Item;
use App\Models\Items\Transactions\Sale;
use App\Models\Items\Transactions\Transaction;
use App\Models\Orders\Evaluation;
use App\Models\Storages\Content;
use App\Models\Users\CardmarketUser;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    const SHIPPING_PROFITS = [
        'Standardbrief' => 0.3,
        'Standardbrief International' => 0.3,
        'Kompaktbrief' => 0.3,
        'Kompaktbrief International' => 0.3,
        'Grossbrief' => 0.5,
        'Grossbrief International' => 0.5,
    ];

    const STATES = [
        'bought' => 'Unbezahlt',
        'paid' => 'Bezahlt',
        'sent' => 'Versandt',
        'received' => 'Angekommen',
        'evaluated' => 'Bewertet',
        'lost' => 'Nicht Angekommen',
        'cancelled' => 'Storniert',
    ];

    protected $appends = [
        'editPath',
        'path',
        'revenue_formatted',
        'paid_at_formatted',
    ];

    protected $dates = [
        'bought_at',
        'canceled_at',
        'paid_at',
        'received_at',
        'sent_at',
    ];

    protected $cardDefaultPrices;

    protected $guarded = [

    ];

    public $incrementing = false;

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
            $model->id = $model->cardmarket_order_id;

            return true;
        });

        static::created(function($model)
        {
            return true;
        });
    }

    public static function updateOrCreateFromCardmarket(int $userId, array $cardmarketOrder) : self
    {
        $buyer = CardmarketUser::updateOrCreateFromCardmarket($cardmarketOrder['buyer']);
        $seller = CardmarketUser::updateOrCreateFromCardmarket($cardmarketOrder['seller']);

        $values = [
            'cardmarket_order_id' => $cardmarketOrder['idOrder'],
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'shipping_method_id' => $cardmarketOrder['shippingMethod']['idShippingMethod'],
            'state' => $cardmarketOrder['state']['state'],
            'shippingmethod' => $cardmarketOrder['shippingMethod']['name'],
            'shipping_name' => $cardmarketOrder['shippingAddress']['name'],
            'shipping_extra' => $cardmarketOrder['shippingAddress']['extra'],
            'shipping_street' => $cardmarketOrder['shippingAddress']['street'],
            'shipping_zip' => $cardmarketOrder['shippingAddress']['zip'],
            'shipping_city' => $cardmarketOrder['shippingAddress']['city'],
            'shipping_country' => $cardmarketOrder['shippingAddress']['country'],
            'shipment_revenue' => $cardmarketOrder['shippingMethod']['price'],
            'articles_count' => $cardmarketOrder['articleCount'],
            'articles_revenue' => $cardmarketOrder['articleValue'],
            'revenue' => $cardmarketOrder['totalValue'],
            'user_id' => $userId,
            'bought_at' => (Arr::has($cardmarketOrder['state'], 'dateBought') ? new Carbon($cardmarketOrder['state']['dateBought']) : null),
            'canceled_at' => (Arr::has($cardmarketOrder['state'], 'dateCanceled') ? new Carbon($cardmarketOrder['state']['dateCanceled']) : null),
            'paid_at' => (Arr::has($cardmarketOrder['state'], 'datePaid') ? new Carbon($cardmarketOrder['state']['datePaid']) : null),
            'received_at' => (Arr::has($cardmarketOrder['state'], 'dateReceived') ? new Carbon($cardmarketOrder['state']['dateReceived']) : null),
            'sent_at' => (Arr::has($cardmarketOrder['state'], 'dateSent') ? new Carbon($cardmarketOrder['state']['dateSent']) : null),
        ];

        $order = self::updateOrCreate(['cardmarket_order_id' => $cardmarketOrder['idOrder']], $values);
        if (Arr::has($cardmarketOrder, 'evaluation')) {
            $evaluation = Evaluation::updateOrCreateFromCardmarket($order->id, $cardmarketOrder['evaluation']);
        }
        if ($order->wasRecentlyCreated) {
            $order->findItems();
            $order->addArticlesFromCardmarket($cardmarketOrder);
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
                    'name' => 'Gewinn',
                    'data' => array_values($profits),
                    'color' => '#28a745',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => 'Kosten',
                    'data' => array_values($costs),
                    'color' => '#dc3545',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => 'Karten',
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
                'text' => 'Bestellungen im ' . $start->monthName
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
                    YEAR(orders.received_at) AS year,
                    MONTH(orders.received_at) AS month,
                    SUM(orders.revenue) AS revenue,
                    SUM(orders.cost) AS cost,
                    SUM(orders.profit) AS profit,
                    SUM(orders.articles_count) AS articles_count,
                    COUNT(*) AS orders_count
                FROM
                    orders
                WHERE
                    orders.user_id = :user_id AND
                    orders.received_at IS NOT NULL AND
                    orders.received_at BETWEEN :start AND :end
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
                    'name' => 'Gewinn',
                    'data' => array_reverse(array_values($profits)),
                    'color' => '#28a745',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => 'Kosten',
                    'data' => array_reverse(array_values($costs)),
                    'color' => '#dc3545',
                    'type' => 'column',
                    'yAxis' => 0,
                ],
                [
                    'name' => 'Karten',
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
                'text' => $year == 0 ? 'Bestellungen der letzten 12 Monate' : 'Bestellungen im ' . $year
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

    public function findItems()
    {
        foreach (Item::where('user_id', $this->user_id)->get() as $key => $item) {
            $quantity = $item->quantity($this);
            if ($quantity == 0) {
                continue;
            }

            $this->sales()->create([
                'item_id' => $item->id,
                'quantity' => $quantity,
                'type' => Sale::class,
                'unit_cost' => $item->unit_cost,
                'user_id' => $this->user_id,
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

    public function addArticlesFromCardmarket(array $cardmarketOrder)
    {
        $this->getCardDefaultPrices();

        foreach ($cardmarketOrder['article'] as $cardmarketArticle) {
            // dump($cardmarketArticle);
            $this->addArticleFromCardmarket($cardmarketArticle);
        }
    }

    protected function addArticleFromCardmarket(array $cardmarketArticle)
    {
        // Card vorhanden?
        // Article finden, wenn nicht vorhanden
        $articles = Article::where('order_id', $this->id)
            ->where('cardmarket_article_id', $cardmarketArticle['idArticle'])
            ->where('user_id', $this->user_id)
            ->limit($cardmarketArticle['count'])
            ->get();
        $articles_count = count($articles);
        $articles_left_count = ($cardmarketArticle['count'] - $articles_count);
        if ($articles_left_count == 0) {
            return;
        }

        // Article mit cardmarket_article_id
        $articles_count = Article::where('articles.user_id', $this->user_id)
            ->whereNull('order_id')
            ->where('articles.cardmarket_article_id', $cardmarketArticle['idArticle'])
            ->where('articles.language_id', $cardmarketArticle['language']['idLanguage'])
            ->where('articles.condition', $cardmarketArticle['condition'])
            ->where('articles.unit_price', $cardmarketArticle['price'])
            ->where('is_foil', $cardmarketArticle['isFoil'])
            ->where('is_signed', $cardmarketArticle['isSigned'])
            ->where('is_altered', $cardmarketArticle['isAltered'])
            ->where('is_playset', $cardmarketArticle['isPlayset'])
            ->limit($articles_left_count)
            ->update([
                'articles.order_id' => $this->id,
                'articles.sold_at' => $this->paid_at,
            ]);
        $articles_left_count -= $articles_count;
        if ($articles_left_count == 0) {
            return;
        }

        $articles_count = Article::join('cards', 'cards.id', '=', 'articles.card_id')
            ->where('articles.user_id', $this->user_id)
            ->whereNull('order_id')
            ->where('cards.cardmarket_product_id', $cardmarketArticle['idProduct'])
            ->where('articles.language_id', $cardmarketArticle['language']['idLanguage'])
            ->where('articles.condition', $cardmarketArticle['condition'])
            ->where('articles.unit_price', $cardmarketArticle['price'])
            ->where('is_foil', $cardmarketArticle['isFoil'])
            ->where('is_signed', $cardmarketArticle['isSigned'])
            ->where('is_altered', $cardmarketArticle['isAltered'])
            ->where('is_playset', $cardmarketArticle['isPlayset'])
            ->limit($articles_left_count)
            ->update([
                'articles.order_id' => $this->id,
                'articles.cardmarket_article_id' => $cardmarketArticle['idArticle'],
                'articles.sold_at' => $this->paid_at,
            ]);
        $articles_left_count -= $articles_count;
        if ($articles_left_count == 0) {
            return;
        }

        $card = Card::where('cardmarket_product_id', $cardmarketArticle['idProduct'])->first();

        $attributes = [
            'user_id' => $this->user_id,
            'card_id' => $card->id,
            'language_id' => $cardmarketArticle['language']['idLanguage'],
            'cardmarket_article_id' => $cardmarketArticle['idArticle'],
            'storage_id' => Content::defaultStorage($this->user_id, $card->expansion_id),
            'condition' => $cardmarketArticle['condition'],
            'unit_price' => $cardmarketArticle['price'],
            'unit_cost' => Arr::get($this->cardDefaultPrices, $cardmarketArticle['product']['rarity'], 0.02),
            'sold_at' => $this->paid_at, // "2019-08-30T10:59:53+0200"
            'is_in_shoppingcard' => $cardmarketArticle['inShoppingCart'] ?? false,
            'is_foil' => $cardmarketArticle['isFoil'] ?? false,
            'is_signed' => $cardmarketArticle['isSigned'] ?? false,
            'is_altered' => $cardmarketArticle['isAltered'] ?? false,
            'is_playset' => $cardmarketArticle['isPlayset'] ?? false,
            'cardmarket_comments' => $cardmarketArticle['comments'] ?: null,
        ];
        foreach (range($articles_count, ($articles_left_count - 1)) as $value) {
            $this->articles()->create($attributes);
        }
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

    public function getRevenueFormattedAttribute()
    {
        return number_format($this->revenue, 2, ',', '');
    }

    public function getPreparedMessageAttribute() : string
    {
        $images_count = count($this->images);
        $message = "Hallo " . $this->buyer->firstname . ",\nvielen Dank für deine Bestellung.\n\n";

        $articlesWithStateComments = $this->articles()->with(['card.localizations'])->whereNotNull('state_comments')->get();
        if (count($articlesWithStateComments) > 0) {
            $message .= "Folgendes ist mir aufgefallen:\n";
            foreach ($articlesWithStateComments as $key => $article) {
                $message .= $article->localName . " " . $article->state_comments . "\n";
            }
            $message .= "\n";
        }

        if ($images_count) {
            if ($images_count == 1) {
                $message .= "Hier ist schon mal ein Bild deiner " . ($this->articles_count == 1 ? 'Karte' : 'Karten') . "\n";
            }
            elseif ($images_count > 1){
                $message .= "Hier sind schon mal Bilder deiner " . ($this->articles_count == 1 ? 'Karte' : 'Karten') . "\n";
            }
            $message .= url($this->path . '/images') . "\n\n";
        }

        $message .= "Ich verschicke sie heute Nachmittag.\n\n";

        $message .= "Viele Grüße\n" . $this->seller->firstname;

        return $message;
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

    public function getShippingAddressTextAttribute() : string
    {
        return $this->shipping_name . "\n" . ($this->shipping_extra ? $this->shipping_extra . "\n" : '') . $this->shipping_street . "\n" . $this->shipping_zip . ' ' . $this->shipping_city . "\n" . $this->shipping_country;
    }

    public function getStateFormattedAttribute() : string
    {
        return Arr::get(self::STATES, $this->state, '');
    }

    public function articles() : HasMany
    {
        return $this->hasMany(Article::class, 'order_id')->with([
            'card.localizations',
            'card.expansion',
        ]);
    }

    public function buyer() : BelongsTo
    {
        return $this->belongsTo(CardmarketUser::class, 'buyer_id');
    }

    public function evaluation() : HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    public function images() : MorphMany
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

    public function scopeState(Builder $query, $value) : Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->where('state', $value);
    }

}
