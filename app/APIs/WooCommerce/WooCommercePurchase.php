<?php

namespace App\APIs\WooCommerce;

class WooCommercePurchase extends WooCommerce
{
    public function __construct()
    {
        $this->url = config('services.woocommerce.purchase.url');
        $this->consumer_key = config('services.woocommerce.purchase.consumer_key');
        $this->consumer_secret = config('services.woocommerce.purchase.consumer_secret');
    }
}