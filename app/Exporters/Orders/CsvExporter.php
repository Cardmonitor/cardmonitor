<?php

namespace App\Exporters\Orders;

use App\Enums\ExternalIds\ExternalType;
use App\Support\Csv\Csv;
use App\Models\Orders\Order;
use App\Models\Articles\Article;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CsvExporter
{
    const BUYER_ATTRIBUTES = [
        'id',
        'username',
        'firstname',
        'lastname',
        'name',
        'extra',
        'street',
        'zip',
        'city',
        'country',
        'country_name',
    ];
    const ORDER_ATTRIBUTES = [
        'mkm',
        'mkm_name',
        'source_id',
        'paid_at',
        'shippingmethod',
        'shipping_name',
        'shipping_extra',
        'shipping_street',
        'shipping_zip',
        'shipping_city',
        'shipping_country',
        'shipping_country_name',
        'revenue',

    ];
    const ARTICLE_ATTRIBUTES = [
        'card_id',
        'sku',
        'cardmarket_article_id',
        'local_name',
        'order_export_name',
        'unit_price',
        'amount',
        'position_type',
    ];

    public static function all(int $userId, Collection $orders, string $path)
    {
        $header = array_merge(self::BUYER_ATTRIBUTES, self::ORDER_ATTRIBUTES, self::ARTICLE_ATTRIBUTES);
        $amount_key = array_search('amount', $header);
        $cardmarket_article_id_key = array_search('cardmarket_article_id', $header);
        $collection = new Collection();
        foreach ($orders as $key => $order) {
            // if ($order->isPresale()) {
            //     continue;
            // }

            if (count($order->articles) == 0) {
                continue;
            }

            $buyer_values = array_values($order->buyer->only(self::BUYER_ATTRIBUTES));
            $order_values = array_values($order->only(self::ORDER_ATTRIBUTES));
            $amount = 1;
            $last_article_external_id = self::getArticleExternalId($order, $order->articles->first());
            $item = [];
            foreach ($order->articles as $key => $article) {
                $article_external_id = self::getArticleExternalId($order, $article);
                if ($last_article_external_id != $article_external_id) {
                    $collection->push($item);
                    $amount = 1;
                }
                $item = array_merge($buyer_values, $order_values, array_values($article->only(self::ARTICLE_ATTRIBUTES)));
                $item[$amount_key] = $amount;
                $item[$cardmarket_article_id_key] = $article_external_id;

                $last_article_external_id = $article_external_id;
                $amount++;
            }
            $collection->push($item);

            $collection->push(self::shippingItem($buyer_values, $order, $article));
        }

        $csv = new Csv();
        $csv->collection($collection)
            ->header($header)
            ->callback( function($item) {
                return $item;
            })->save(Storage::disk('public')->path($path));

        return Storage::disk('public')->url($path);
    }

    private static function getArticleExternalId(Order $order, Article $acticle)
    {
        return match ($order->source_slug) {
            ExternalType::CARDMARKET->value => $acticle->externalIdsCardmarket?->external_id,
            ExternalType::WOOCOMMERCE->value => $acticle->externalIdsWoocommerce?->external_id,
            default => null,
        };
    }

    private static function shippingItem(array $buyer_values, Model $order, Model $article) : array
    {
        $shippingValuesArticle = $article->only(self::ARTICLE_ATTRIBUTES);
        $shippingValuesArticle['unit_price'] = $order->shipment_revenue;
        $shippingValuesArticle['position_type'] = 'Versandposition';
        $shippingValuesArticle['local_name'] = $order->shippingmethod;
        $shippingValuesArticle['order_export_name'] = $order->shippingmethod;
        $shippingValuesArticle['card_id'] = '';
        $shippingValuesArticle['sku'] = '';
        $shippingValuesArticle['cardmarket_article_id'] = '';
        $shippingValuesArticle['amount'] = 1;

        $shippingValuesOrder = $order->only(self::ORDER_ATTRIBUTES);
        $shippingValuesOrder['revenue'] = $order->shipment_revenue;

        return array_merge($buyer_values, array_values($shippingValuesOrder), array_values($shippingValuesArticle));
    }
}
