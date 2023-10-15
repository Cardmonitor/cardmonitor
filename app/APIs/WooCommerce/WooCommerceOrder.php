<?php

namespace App\APIs\WooCommerce;

class WooCommerceOrder extends WooCommercePurchase
{
    public function __construct()
    {
        $this->url = config('services.woocommerce.order.url');
        $this->consumer_key = config('services.woocommerce.order.consumer_key');
        $this->consumer_secret = config('services.woocommerce.order.consumer_secret');
    }
}