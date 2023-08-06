<?php

namespace App\Importers\Orders;

use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use App\Models\Orders\Order;
use Illuminate\Support\Carbon;
use App\Models\Articles\Article;
use App\Models\Users\CardmarketUser;
use App\Models\Localizations\Language;

class WooCommerceOrderImporter
{
    CONST SOURCE_SLUG = 'woocommerce-api';

    private array $articles = [];
    private int $user_id;

    private float $additional_unit_cost = 0;
    private float $bonus = 0;
    private int $source_sort = 1;

    private Order $order;

    public static function import(int $user_id, array $woocommerce_order): void
    {
        $importer = new self($user_id, $woocommerce_order);
        $importer->importOrder($woocommerce_order);
    }

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function importOrder(array $woocommerce_order): array
    {
        $this->setBonus($woocommerce_order);
        $this->createOrder($woocommerce_order);

        foreach ($woocommerce_order['line_items'] as $line_item) {

            if (strpos($line_item['sku'], '-') === false) {
                continue;
            }

            $this->importLineItem($line_item);
        }

        return $this->articles;
    }

    public function createOrder(array $woocommerce_order): void
    {
        $seller = $this->updateOrCreateSeller($woocommerce_order);

        $articles_count = 0;
        $articles_cost = 0;
        foreach ($woocommerce_order['line_items'] as $line_item) {
            [$cardmarket_product_id] = explode('-', $line_item['sku']);
            if (! is_numeric($cardmarket_product_id)) {
                continue;
            }
            $articles_cost += $line_item['total'];
            $articles_count += $line_item['quantity'];
        }

        $values = [
            'cardmarket_order_id' => 0,
            'buyer_id' => null,
            'seller_id' => $seller->id,
            'shipping_method_id' => 0,
            'state' => $woocommerce_order['status'],
            'shippingmethod' => '',
            'shipping_name' => $woocommerce_order['shipping']['first_name'] . ' ' . $woocommerce_order['shipping']['last_name'],
            'shipping_extra' => '',
            'shipping_street' => $woocommerce_order['shipping']['address_1'],
            'shipping_zip' => $woocommerce_order['shipping']['postcode'],
            'shipping_city' => $woocommerce_order['shipping']['city'],
            'shipping_country' => $woocommerce_order['shipping']['country'],
            'shipment_revenue' => $woocommerce_order['shipping_total'],
            'articles_count' => $articles_count,
            'articles_cost' => $articles_cost,
            'cost' => $woocommerce_order['total'],
            'user_id' => $this->user_id,
            'bought_at' => new Carbon($woocommerce_order['date_completed_gmt']),
            'canceled_at' => null,
            'paid_at' => new Carbon($woocommerce_order['date_paid_gmt']),
            'received_at' => null,
            'sent_at' => null,
            'is_purchase' => true,
        ];

        $this->order = Order::updateOrCreate([
            'source_slug' => self::SOURCE_SLUG,
            'source_id' => $woocommerce_order['id'],
        ], $values);
    }

    public function updateOrCreateSeller(array $woocommerce_order): CardmarketUser
    {
        return CardmarketUser::updateOrCreate([
            'source_slug' => self::SOURCE_SLUG,
            'firstname' => $woocommerce_order['billing']['first_name'],
            'name' => $woocommerce_order['billing']['last_name'],
            'extra' => $woocommerce_order['billing']['address_2'],
            'street' => $woocommerce_order['billing']['address_1'],
            'zip' => $woocommerce_order['billing']['postcode'],
            'city' => $woocommerce_order['billing']['city'],
            'country' => $woocommerce_order['billing']['country'],
            'phone' => $woocommerce_order['billing']['phone'],
            'email' => $woocommerce_order['billing']['email'],
        ], [
            'source_id' => $woocommerce_order['customer_id'],
            'cardmarket_user_id' => 0,
            'username' => '',
            'registered_at' => now(),
            'is_commercial' => false,
            'is_seller' => false,
            'vat' => '',
            'legalinformation' => '',
            'risk_group' => 0,
            'loss_percentage' => '',
            'unsent_shipments' => 0,
            'reputation' => 0,
            'ships_fast' => 0,
            'sell_count' => 0,
            'sold_items' => 0,
            'avg_shipping_time' => 0,
            'is_on_vacation' => false,
        ]);
    }

    public function setBonus(array $woocommerce_order): void
    {
        $this->bonus = ($woocommerce_order['payment_method'] == 'cod') ? 0.15 : 0;
    }

    public function importLineItem(array $line_item): void
    {
        [$cardmarket_product_id, $is_foil] = explode('-', $line_item['sku']);
        // Positionen ohne Cardmarket-Produkt-ID ignorieren, z.B. Bulk Rares
        if (! is_numeric($cardmarket_product_id)) {
            return;
        }

        $card = Card::firstOrImport($cardmarket_product_id);

        $language = Arr::first($line_item['meta_data'], function ($meta) {
            return str_starts_with($meta['key'], 'sprache');
        });

        $condition = Arr::first($line_item['meta_data'], function ($meta) {
            return $meta['key'] == 'zustand';
        });

        $unit_cost = $line_item['total'] / $line_item['quantity'] * (1 + $this->bonus);

        for ($index=1; $index <= $line_item['quantity']; $index++) {
            $values = [
                'source_slug' => self::SOURCE_SLUG,
                'source_id' => $line_item['id'],
                'index' => $index,
                'user_id' => $this->user_id,
                'card_id' => $card->id,
                'language_id' => Language::getIdByGermanName($language['value']),
                'cardmarket_article_id' => null,
                'condition' => array_search(substr($condition['value'], 0, strrpos($condition['value'], ' ')), Article::CONDITIONS),
                'unit_price' => $unit_cost * 3,
                'unit_cost' => $unit_cost,
                'sold_at' => null,
                'is_in_shoppingcard' => false,
                'is_foil' => ($is_foil == 'true'),
                'is_signed' => false,
                'is_altered' => false,
                'is_playset' => false,
                'cardmarket_comments' => null,
                'has_sync_error' => false,
                'sync_error' => null,
                'storage_id' => null,
                'source_sort' => $this->source_sort,
            ];
            $attributes = [
                'source_slug' => self::SOURCE_SLUG,
                'source_id' => $line_item['id'],
                'index' => $index,
            ];

            $this->articles[] = $this->order->articles()->updateOrCreate($attributes, $values);

            $this->source_sort++;
        }
    }
}