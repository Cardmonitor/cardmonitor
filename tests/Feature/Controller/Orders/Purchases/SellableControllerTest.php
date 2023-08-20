<?php

namespace Tests\Feature\Controller\Orders\Purchases;

use Tests\TestCase;
use App\Models\Cards\Card;
use Illuminate\Support\Facades\Auth;
use Tests\Support\Snapshots\JsonSnapshot;

class SellableControllerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_make_a_purchase_sellable()
    {
        $woocommerce_order_id = 619687;
        $woocommerce_order_response = JsonSnapshot::get('tests/snapshots/woocommerce/orders/' . $woocommerce_order_id . '.json', function () use ($woocommerce_order_id) {
            return (new \App\APIs\WooCommerce\WooCommerce())->order($woocommerce_order_id);
        });
        $woocommerce_order = $woocommerce_order_response['data'];

        $quantity = 0;
        foreach ($woocommerce_order['line_items'] as $line_item) {
            $cardmarket_product_id = substr($line_item['sku'], 0, strpos($line_item['sku'], '-'));

            if (! is_numeric($cardmarket_product_id)) {
                continue;
            }
            factory(Card::class)->create([
                'game_id' => \App\Models\Games\Game::ID_MAGIC,
                'cardmarket_product_id' => $cardmarket_product_id,
            ]);

            $quantity += $line_item['quantity'];
        }

        $order = \App\Importers\Orders\WooCommerceOrderImporter::import($this->user->id, $woocommerce_order);
        $order->loadMissing('articles');

        $this->assertCount($quantity, $order->articles);

        foreach ($order->articles as $article) {
            $this->assertEquals(0, $article->is_sellable);
            $article->update([
                'state' => \App\Models\Articles\Article::STATE_OK,
            ]);
        }

        $order->articles->first()->update([
            'state' => \App\Models\Articles\Article::STATE_NOT_PRESENT,
        ]);

        $this->signIn(factory(\App\User::class)->create());

        $response = $this->post(route('purchases.cancel.store', $order));
        $response->assertForbidden();

        Auth::logout();

        $this->signIn();

        $response = $this->post(route('purchases.sellable.store', $order));
        $response->assertOk();

        $this->assertDatabaseHas('storages', [
            'user_id' => $order->user_id,
            'name' => 'WooCommerce',
        ]);

        $this->assertDatabaseHas('storages', [
            'user_id' => $order->user_id,
            'name' => 'Bestellung #' . $order->source_id,
        ]);

        $order->refresh();

        $this->assertCount($quantity - 1, $order->articles);
        foreach ($order->articles as $article) {
            $this->assertEquals(1, $article->is_sellable);
            $this->assertNotNull($article->is_sellable_since);
            $this->assertNotNull($article->storage_id);
        }

    }
}
