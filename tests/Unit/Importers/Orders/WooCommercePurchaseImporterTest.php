<?php

namespace Tests\Unit\Importers\Orders;

use Mockery;
use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use App\Models\Orders\Order;
use App\APIs\WooCommerce\Status;
use App\Models\Articles\Article;
use App\Models\Users\CardmarketUser;
use App\APIs\WooCommerce\WooCommercePurchase;
use App\Enums\Articles\Source;
use Tests\Support\Snapshots\JsonSnapshot;
use App\Importers\Orders\WooCommercePurchaseImporter;

class WooCommercePurchaseImporterTest extends TestCase
{
    /**
     * @test
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    public function it_can_update_or_create_a_purchase_from_woocommerce_api()
    {
        $woocommerce_order_id = 627483;
        $woocommerce_order_response = JsonSnapshot::get('tests/snapshots/woocommerce/purchases/' . $woocommerce_order_id . '.json', function () use ($woocommerce_order_id) {
            return (new \App\APIs\WooCommerce\WooCommercePurchase())->order($woocommerce_order_id)->json();
        });
        $woocommerce_order = $woocommerce_order_response['data'];

        $quantity = 0;
        foreach ($woocommerce_order['line_items'] as $line_item) {
            if (! WooCommercePurchaseImporter::hasCardmarketId($line_item['sku'])) {
                $quantity++;
                continue;
            }
            $cardmarket_product_id = substr($line_item['sku'], 0, strpos($line_item['sku'], '-'));
            factory(Card::class)->create([
                'game_id' => Game::ID_MAGIC,
                'cardmarket_product_id' => $cardmarket_product_id,
            ]);

            $quantity += $line_item['quantity'];
        }

        $this->assertCount(0, Article::all());
        $this->assertCount(0, Order::all());
        $this->assertCount(0, CardmarketUser::all());

        $woocommerce_mock = Mockery::mock('overload:' . WooCommercePurchase::class);
        $woocommerce_mock->shouldReceive('updateOrderState')
            ->with($woocommerce_order_id, Status::PROCESSING)
            ->once();

        WooCommercePurchaseImporter::import($this->user->id, $woocommerce_order);

        $this->assertCount($quantity, Article::all());
        $this->assertCount(1, Order::all());
        $this->assertCount(1, CardmarketUser::all());

        $order = Order::with([
            'articles',
            'seller',
        ])->first();

        $this->assertEquals($woocommerce_order['billing']['first_name'], $order->seller->firstname);
        $this->assertEquals($woocommerce_order['billing']['last_name'], $order->seller->name);
        $this->assertEquals($woocommerce_order['billing']['address_1'], $order->seller->street);
        $this->assertEquals($woocommerce_order['billing']['postcode'], $order->seller->zip);
        $this->assertEquals($woocommerce_order['billing']['city'], $order->seller->city);
        $this->assertEquals($woocommerce_order['billing']['country'], $order->seller->country);
        $this->assertEquals($woocommerce_order['billing']['email'], $order->seller->email);
        $this->assertEquals($woocommerce_order['billing']['phone'], $order->seller->phone);

        $this->assertEquals(1, $order->is_purchase);
        $this->assertEquals($this->user->id, $order->user_id);
        $this->assertEquals($woocommerce_order['id'], $order->source_id);
        $this->assertEquals(Source::WOOCOMMERCE_PURCHASE->value, $order->source_slug);
        $this->assertEquals($order->seller->id, $order->seller_id);
        $this->assertEquals(Status::PROCESSING->value, $order->state);
        $this->assertEquals($quantity, $order->articles_count);

        $source_sort = 1;
        foreach ($order->articles as $key => $article) {
            $line_item = $woocommerce_order['line_items'][$key];
            if (WooCommercePurchaseImporter::hasCardmarketId($line_item['sku'])) {
                [$cardmarket_product_id, $is_foil] = explode('-', $line_item['sku']);
            }
            else {
                $cardmarket_product_id = null;
                $is_foil = false;
            }

            $this->assertEquals(Source::WOOCOMMERCE_PURCHASE->value, $article->source_slug);
            $this->assertEquals($line_item['id'], $article->source_id);
            $this->assertEquals($source_sort, $article->source_sort);
            $this->assertEquals($cardmarket_product_id, $article->card_id);
            $this->assertEquals(round($line_item['total'] * (1 + 0.15), 6), $article->unit_cost);
            $this->assertEquals(round($line_item['total'] * (1 + 0.15) * 3, 6), $article->unit_price);
            $this->assertEquals(WooCommercePurchaseImporter::getCondition($line_item), $article->condition);
            $this->assertEquals($is_foil === 'true', $article->is_foil);
            $this->assertEquals(\App\Models\Localizations\Language::DEFAULT_ID, $article->language_id);
            $this->assertEquals(0, $article->is_sellable);
            $this->assertNull($article->is_sellable_since);

            $source_sort++;
        }

        WooCommercePurchaseImporter::import($this->user->id, $woocommerce_order);

        $this->assertCount(1, Order::all());
        $this->assertCount($quantity, Article::all());
        $this->assertCount(1, CardmarketUser::all());
    }
}
