<?php

namespace Tests\Unit\APIs\WooCommerce;

use Tests\TestCase;

class WooCommerceTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_all_orders()
    {
        $this->markTestSkipped();

        $WooCommerce = new \App\APIs\WooCommerce\WooCommercePurchase();
        $response = $WooCommerce->orders();

        echo PHP_EOL;
        foreach ($response->json() as $order) {
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
        $WooCommerce = new \App\APIs\WooCommerce\WooCommercePurchase();
        $response = $WooCommerce->order($id);
        $order = $response->json();

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
        $WooCommerce = new \App\APIs\WooCommerce\WooCommercePurchase();
        $response = $WooCommerce->updateOrder($id, [
            'status' => 'on-hold',
        ]);
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    /**
     * @test
     */
    public function it_gets_all_products()
    {
        $this->markTestSkipped();

        $WooCommerce = new \App\APIs\WooCommerce\WooCommerceOrder();
        $product_response = $WooCommerce->products();
        echo PHP_EOL;
        foreach ($product_response->json() as $product) {
            dump($product);
        }

        $this->assertTrue(true);
    }
}
