<?php

namespace App\Importers\Orders;

use App\Enums\Orders\Status;
use App\Models\Orders\Order;
use Illuminate\Support\Carbon;
use App\Models\Articles\Article;
use App\Models\Users\CardmarketUser;
use App\Enums\ExternalIds\ExternalType;

class WooCommerceOrderImporter
{
    private array $articles = [];
    private int $user_id;

    private Order $order;

    public static function import(int $user_id, array $woocommerce_order): Order
    {
        $importer = new self($user_id, $woocommerce_order);
        return $importer->importOrder($woocommerce_order);
    }

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function importOrder(array $woocommerce_order): Order
    {
        $this->createOrder($woocommerce_order);

        foreach ($woocommerce_order['line_items'] as $line_item) {
            $this->importLineItem($line_item);
        }

        return $this->order;
    }

    public function createOrder(array $woocommerce_order): void
    {
        $buyer = $this->updateOrCreateBuyer($woocommerce_order);

        $articles_count = 0;
        $articles_cost = 0;
        foreach ($woocommerce_order['line_items'] as $line_item) {
            $articles_cost += $line_item['total'];
            $articles_count += $line_item['quantity'];
        }

        $values = [
            'cardmarket_order_id' => 0,
            'buyer_id' => $buyer->id,
            'seller_id' => null,
            'shipping_method_id' => 0,
            'state' => Status::fromWooCommerceSlug($woocommerce_order['status'])->value,
            'shippingmethod' => '',
            'shipping_name' => $woocommerce_order['shipping']['first_name'] . ' ' . $woocommerce_order['shipping']['last_name'],
            'shipping_extra' => '',
            'shipping_street' => $woocommerce_order['shipping']['address_1'],
            'shipping_zip' => $woocommerce_order['shipping']['postcode'],
            'shipping_city' => $woocommerce_order['shipping']['city'],
            'shipping_country' => $woocommerce_order['shipping']['country'],
            'shipment_revenue' => $woocommerce_order['shipping_total'],
            'articles_count' => $articles_count,
            'articles_revenue' => $articles_cost,
            'articles_cost' => 0,
            'revenue' => $woocommerce_order['total'],
            'cost' => 0,
            'user_id' => $this->user_id,
            'bought_at' => new Carbon($woocommerce_order['date_created_gmt']),
            'canceled_at' => null,
            'paid_at' => new Carbon($woocommerce_order['date_paid_gmt']),
            'received_at' => new Carbon($woocommerce_order['date_completed_gmt']),
            'sent_at' => new Carbon($woocommerce_order['date_completed_gmt']),
            'is_purchase' => false,
        ];

        $this->order = Order::updateOrCreate([
            'source_slug' => ExternalType::WOOCOMMERCE->value,
            'source_id' => $woocommerce_order['id'],
        ], $values);
    }

    public function updateOrCreateBuyer(array $woocommerce_order): CardmarketUser
    {
        return CardmarketUser::updateOrCreate([
            'source_slug' => ExternalType::WOOCOMMERCE->value,
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

    public function importLineItem(array $line_item): void
    {
        $article = $this->findArticle($line_item);
        $this->order->articles()->syncWithoutDetaching([$article->id]);
        $article->update([
            'sold_at' => $this->order->paid_at ?? $this->order->bought_at,
            'unit_price' => $line_item['price'],
        ]);
        $this->articles[] = $article;
    }

    private function findArticle(array $line_item): Article
    {
        // article already in order
        $article = $this->order->articles()->where('number', $line_item['sku'])->first();
        if (!is_null($article)) {
            return $article;
        }

        // article not in order, find from sellable articles
        $article = $this->findSellableArticle($line_item);
        if (!is_null($article)) {
            return $article;
        }

        // article not found, create article from woocommerce
        return $this->createArticleFromWooCommerce($line_item);
    }

    private function findSellableArticle(array $line_item): ?Article
    {
        return Article::where('user_id', $this->user_id)
            ->sold(0)
            ->where('number', $line_item['sku'])
            ->first();
    }

    private function createArticleFromWooCommerce(array $line_item): Article
    {
        $woocommerce_product_id = $line_item['product_id'];
        $woocommerce_product = (new \App\APIs\WooCommerce\WooCommerceOrder())->findProduct($woocommerce_product_id)->json();

        return Article::createFromWooCommerceProduct($this->user_id, $woocommerce_product);
    }
}
