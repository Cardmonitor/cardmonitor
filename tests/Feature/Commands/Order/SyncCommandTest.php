<?php

namespace Tests\Feature\Commands\Order;

use App\Console\Commands\Order\SyncCommand;
use App\Models\Apis\Api;
use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Items\Item;
use App\Models\Orders\Order;
use App\Models\Users\CardmarketUser;
use Tests\TestCase;

class SyncCommandTest extends TestCase
{
    private Api $api;
    private Expansion $expansion;
    private Item $item;

    protected function setUp() : void
    {
        parent::setUp();

        $this->api = factory(Api::class)->create([
            'user_id' => $this->user->id,
            'access_token' => 'LMDxSPkFfCBIYTULl3yHdswrwbYCZEzf',
            'access_token_secret' => 'PgHYR3j8o0Itktu47AbkRRE1foccd91r',
        ]);

        $this->expansion = factory(Expansion::class)->create([
            'name' => 'Born of the Gods',
        ]);
        $this->expansion->cards()->create(factory(Card::class)->make([
            'cardmarket_product_id' => 265535,
        ])->toArray());

        $this->expansion->cards()->create(factory(Card::class)->make([
            'cardmarket_product_id' => 360083,
        ])->toArray());

        $this->item = factory(Item::class)->create([
            'user_id' => $this->api->user_id,
            'unit_cost' => 1,
        ]);
        $this->item->quantities()->create([
            'effective_from' => '1970-00-00 02:00:00',
            'end' => 9999,
            'quantity' => 1,
            'start' => 1,
            'user_id' => $this->api->user_id,
        ]);
        $this->item = factory(Item::class)->create([
            'user_id' => $this->api->user_id,
            'unit_cost' => 0.5,
        ]);
        $this->item->quantities()->create([
            'effective_from' => '1970-00-00 02:00:00',
            'end' => 9999,
            'quantity' => 2,
            'start' => 1,
            'user_id' => $this->api->user_id,
        ]);
    }

    /**
     * @test
     */
    public function it_syncs_orders_seller_received()
    {
        SyncCommand::dispatch([
            'user' => $this->user->id,
            '--state' => Order::STATE_CANCELLED,
        ]);
        exit;

        $this->markTestSkipped();

        $this->artisan('order:sync', [
            'user' => $this->user->id,
        ]);

        $order = Order::with([
            'articles',
            'sales',
        ])->orderBy('id', 'DESC')
        ->first();

        dd($order);

        $this->assertCount(3, $order->articles);
        $this->assertEquals(0.3, $order->articles_cost);

        $this->assertCount(5, $order->sales);
        $this->assertEquals(2.0579, $order->items_cost);

        $this->assertCount(2, CardmarketUser::all());
    }

    /**
     * @test
     */
    public function it_syncs_orders_seller_paid()
    {
        $this->markTestSkipped();

        $this->artisan('order:sync --state=paid');

        $order = Order::with([
            'articles',
            'sales',
        ])->orderBy('id', 'DESC')
        ->first();

        $this->assertCount(3, $order->articles);
        $this->assertEquals(0.3, $order->articles_cost);

        $this->assertCount(5, $order->sales);
        $this->assertEquals(2.0579, $order->items_cost);

        $this->assertCount(2, CardmarketUser::all());
    }
}
