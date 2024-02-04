<?php

namespace Tests\Unit\Importers\Orders;

use Mockery;
use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Orders\Order;
use App\APIs\WooCommerce\Status;
use App\Models\Articles\Article;
use App\Models\Users\CardmarketUser;
use App\Enums\ExternalIds\ExternalType;
use Tests\Support\Snapshots\JsonSnapshot;
use App\APIs\WooCommerce\WooCommerceOrder;
use App\Importers\Orders\WooCommerceOrderImporter;

class WooCommerceOrderImporterTest extends TestCase
{
    /**
     * @test
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    public function it_can_update_or_create_an_order_from_woocommerce_api()
    {
        $woocommerce_order_id = 677175;
        $woocommerce_order = JsonSnapshot::get('tests/snapshots/woocommerce/orders/' . $woocommerce_order_id . '.json', function () use ($woocommerce_order_id) {
            return (new \App\APIs\WooCommerce\WooCommerceOrder())->order($woocommerce_order_id)->json();
        });

        $quantity = 0;
        $meta_datas = [];
        foreach ($woocommerce_order['line_items'] as $line_item) {
            $woocommerce_product_id = $line_item['product_id'];
            $woocommerce_product = JsonSnapshot::get('tests/snapshots/woocommerce/orders/products/' . $woocommerce_product_id . '.json', function () use ($woocommerce_product_id) {
                return (new \App\APIs\WooCommerce\WooCommerceOrder())->findProduct($woocommerce_product_id)->json();
            });
            $meta_data = array_reduce($woocommerce_product['meta_data'], function ($carry, $item) {
                $carry[$item['key']] = $item['value'];
                return $carry;
            }, []);
            factory(Card::class)->create([
                'game_id' => $meta_data['game_id'],
                'cardmarket_product_id' => $meta_data['card_id'],
            ]);

            $quantity += $line_item['quantity'];
            $meta_datas[] = $meta_data;
        }

        $this->assertCount(0, Article::all());
        $this->assertCount(0, Order::all());
        $this->assertCount(0, CardmarketUser::all());

        WooCommerceOrderImporter::import($this->user->id, $woocommerce_order);

        $this->assertCount($quantity, Article::all());
        $this->assertCount(1, Order::all());
        $this->assertCount(1, CardmarketUser::all());

        $order = Order::with([
            'articles',
            'buyer',
        ])->first();

        $this->assertEquals($woocommerce_order['billing']['first_name'], $order->buyer->firstname);
        $this->assertEquals($woocommerce_order['billing']['last_name'], $order->buyer->name);
        $this->assertEquals($woocommerce_order['billing']['address_1'], $order->buyer->street);
        $this->assertEquals($woocommerce_order['billing']['postcode'], $order->buyer->zip);
        $this->assertEquals($woocommerce_order['billing']['city'], $order->buyer->city);
        $this->assertEquals($woocommerce_order['billing']['country'], $order->buyer->country);
        $this->assertEquals($woocommerce_order['billing']['email'], $order->buyer->email);
        $this->assertEquals($woocommerce_order['billing']['phone'], $order->buyer->phone);

        $this->assertEquals(0, $order->is_purchase);
        $this->assertEquals($this->user->id, $order->user_id);
        $this->assertEquals($woocommerce_order['id'], $order->source_id);
        $this->assertEquals(ExternalType::WOOCOMMERCE->value, $order->source_slug);
        $this->assertEquals($order->buyer->id, $order->buyer_id);
        $this->assertEquals($woocommerce_order['status'], $order->state);
        $this->assertEquals($quantity, $order->articles_count);

        foreach ($order->articles as $key => $article) {

            $meta_data = $meta_datas[$key];

            $this->assertEquals($meta_data['card_id'], $article->card_id);
            $this->assertEquals(round($line_item['total'], 6), $article->unit_price);
            $this->assertEquals($meta_data['condition'], $article->condition);
            $this->assertEquals((int) ($meta_data['is_foil'] === 'Ja'), $article->is_foil);
            $this->assertEquals($meta_data['language_id'], $article->language_id);
        }

        WooCommerceOrderImporter::import($this->user->id, $woocommerce_order);

        $this->assertCount(1, Order::all());
        $this->assertCount($quantity, Article::all());
        $this->assertCount(1, CardmarketUser::all());
    }
}
