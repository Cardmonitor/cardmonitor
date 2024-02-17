<?php

namespace App\Http\Controllers\Orders\WooCommerce;

use App\APIs\WooCommerce\WooCommerceOrder;
use App\Enums\ExternalIds\ExternalType;
use App\Models\Orders\Order;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function show(Order $order)
    {
        if (!$order->is_woocommerce) {
            return back()->with('status', [
                'type' => 'error',
                'text' => 'Bestellung nicht von WooCommerce.',
            ]);
        }

        return (new WooCommerceOrder())->order($order->source_id)->json();
    }
}
