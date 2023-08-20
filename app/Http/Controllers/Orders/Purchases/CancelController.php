<?php

namespace App\Http\Controllers\Orders\Purchases;

use App\Models\Orders\Order;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

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
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $WooCommerce->updateOrder($order->source_id, [
            'status' => 'cancelled',
        ]);
    }
}
