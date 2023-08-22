<?php

namespace App\Http\Controllers\Orders\Purchases;

use App\Models\Orders\Order;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\APIs\WooCommerce\WooCommerce;

class CancelController extends Controller
{
    public function store(Order $order)
    {
        if (auth()->user()->id !== $order->user_id) {
            abort(403);
        }

        $this->deleteOrder($order);
        // $this->cancelWooCommerceOrder($order);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    private function deleteOrder(Order $order): void
    {
        $order->articles()->delete();
        $order->delete();
    }

    private function cancelWooCommerceOrder(Order $order): void
    {
        $WooCommerce = new WooCommerce();
        $WooCommerce->updateOrder($order->source_id, [
            'status' => WooCommerce::STATUS_CANCELLED,
        ]);
        $order->update([
            'state' => WooCommerce::STATUS_CANCELLED,
        ]);
    }
}
