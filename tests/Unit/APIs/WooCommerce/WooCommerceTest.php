<?php

namespace Tests\Unit\APIs\WooCommerce;

use App\APIs\WooCommerce\Status;
use Tests\TestCase;

class WooCommerceTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_all_orders()
    {
        $this->markTestSkipped();

        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $orders = $WooCommerce->orders();

        echo PHP_EOL;
        foreach ($orders['data'] as $order) {
            echo $order['status'] . PHP_EOL;
        }

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_gets_an_order()
    {
        $this->markTestSkipped();

        $id = 629161;
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $response = $WooCommerce->order($id);
        $order = $response['data'];

        echo PHP_EOL;
        foreach ($order['line_items'] as $line_item) {
            echo $line_item['name'] . ': ' . $line_item['sku'] . PHP_EOL;
        }
    }

    /**
     * @test
     */
    public function it_can_update_the_status_of_an_order()
    {
        $this->markTestSkipped();

        $id = 629161;
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $response = $WooCommerce->updateOrder($id, [
            'status' => 'on-hold',
        ]);
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}
