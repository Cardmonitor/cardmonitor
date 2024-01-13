<?php

namespace Tests\Feature\Controller\Orders\Purchases;

use Mockery;
use Tests\TestCase;
use App\Models\Cards\Card;
use Illuminate\Support\Facades\Auth;
use App\APIs\WooCommerce\WooCommercePurchase;
use Tests\Support\Snapshots\JsonSnapshot;

class CancelControllerTest extends TestCase
{
    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_cancel_a_purchase()
    {
        $woocommerce_order_id = 619687;
        $woocommerce_order_response = JsonSnapshot::get('tests/snapshots/woocommerce/purchases/' . $woocommerce_order_id . '.json', function () use ($woocommerce_order_id) {
            return (new \App\APIs\WooCommerce\WooCommercePurchase())->order($woocommerce_order_id)->json();
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

        $woocommerce_mock = Mockery::mock('overload:' . WooCommercePurchase::class);
        $woocommerce_mock->shouldReceive('updateOrderState')
            ->with($woocommerce_order_id, \App\APIs\WooCommerce\Status::PROCESSING);

        $order = \App\Importers\Orders\WooCommercePurchaseImporter::import($this->user->id, $woocommerce_order);
        $order->loadMissing('articles');

        $this->assertCount($quantity, $order->articles);

        $this->signIn(factory(\App\User::class)->create());

        $response = $this->post(route('purchases.cancel.store', $order));
        $response->assertForbidden();

        Auth::logout();

        $this->signIn();

        $woocommerce_mock->shouldReceive('updateOrderState')
            ->with($woocommerce_order_id, \App\APIs\WooCommerce\Status::CANCELLED)
            ->once();

        $response = $this->post(route('purchases.cancel.store', $order));
        $response->assertNoContent();

        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
            'user_id' => $order->user_id,
        ]);

        foreach ($order->articles as $article) {
            $this->assertDatabaseMissing('articles', [
                'id' => $article->id,
                'user_id' => $article->user_id,
            ]);
        }
    }
}
