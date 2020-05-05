<?php

namespace App\Exporters\Orders;

use App\Support\Csv\Csv;
use Illuminate\Support\Collection;
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
    ];
    const ORDER_ATTRIBUTES = [
        'mkm',
        'mkm_name',
        'cardmarket_order_id',
        'paid_at',
        'shippingmethod',
        'shipping_name',
        'shipping_extra',
        'shipping_street',
        'shipping_zip',
        'shipping_city',
        'shipping_country',
        'revenue',

    ];
    const ARTICLE_ATTRIBUTES = [
        'card_id',
        'cardmarket_article_id',
        'local_name',
        'unit_price',
        'amount',
        'position_type',
    ];

    public static function all(int $userId, Collection $orders, string $path)
    {
        $firstOrder = $orders->first();
        $firstBuyer = $firstOrder->buyer;
        $firstArticle = $firstOrder->articles->first();

        $header = array_merge(array_keys($firstBuyer->only(self::BUYER_ATTRIBUTES)), array_keys($firstOrder->only(self::ORDER_ATTRIBUTES)), array_keys($firstArticle->only(self::ARTICLE_ATTRIBUTES)));

        $collection = new Collection();
        foreach ($orders as $key => $order) {
            $buyer_values = array_values($order->buyer->only(self::BUYER_ATTRIBUTES));
            $order_values = array_values($order->only(self::ORDER_ATTRIBUTES));
            foreach ($order->articles as $key => $article) {
                $item = array_merge($buyer_values, $order_values, array_values($article->only(self::ARTICLE_ATTRIBUTES)));
                $collection->push($item);
            }
            $shippingValuesArticle = $article->only(self::ARTICLE_ATTRIBUTES);
            $shippingValuesArticle['unit_price'] = $order->shipment_revenue;
            $shippingValuesArticle['position_type'] = 'Versandposition';
            $shippingValuesArticle['local_name'] = $order->shippingmethod;
            $shippingValuesArticle['card_id'] = '';
            $shippingValuesArticle['cardmarket_article_id'] = '';

            $shippingValuesOrder = $order->only(self::ORDER_ATTRIBUTES);
            $shippingValuesOrder['revenue'] = $order->shipment_revenue;

            $collection->push(array_merge($buyer_values, array_values($shippingValuesOrder), array_values($shippingValuesArticle)));
        }

        $csv = new Csv();
        $csv->collection($collection)
            ->header($header)
            ->callback( function($item) {
                return $item;
            })->save(Storage::disk('public')->path($path));

        return Storage::disk('public')->url($path);
    }

    protected static function shippingItem(array $buyer_values, array $order, array $article) : array
    {
        return [];
    }
}