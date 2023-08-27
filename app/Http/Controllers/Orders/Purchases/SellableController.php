<?php

namespace App\Http\Controllers\Orders\Purchases;

use App\APIs\WooCommerce\WooCommerce;
use App\Models\Orders\Order;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Http\Controllers\Controller;

class SellableController extends Controller
{
    public function store(Order $order)
    {
        if (auth()->user()->id !== $order->user_id) {
            abort(403);
        }

        $this->deleteNotAvailableArticles($order);
        $this->completeWooCommerceOrder($order);

        $order->articles()->update([
            'is_sellable_since' => now(),
            'is_sellable' => true,
            'storage_id' => $this->getStorage($order)->id,
        ]);

        return $order->load([
            'articles' => function ($query) {
                $query->with([
                    'card.expansion',
                ]);
            },
        ]);
    }

    private function deleteNotAvailableArticles(Order $order): void
    {
        $order->articles()->where('state', Article::STATE_NOT_PRESENT)->delete();
    }

    private function completeWooCommerceOrder(Order $order): void
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $WooCommerce->updateOrder($order->source_id, [
            'status' => WooCommerce::STATUS_COMPLETED,
        ]);
        $order->update([
            'state' => WooCommerce::STATUS_COMPLETED,
        ]);
    }

    private function getStorage(Order $order): Storage
    {
        $storage_woocommerce = Storage::firstOrCreate([
            'user_id' => $order->user_id,
            'name' => 'WooCommerce',
        ]);

        return Storage::firstOrCreate([
            'user_id' => $order->user_id,
            'name' => 'Bestellung #' . $order->source_id,
            'parent_id' => $storage_woocommerce->id,
        ]);
    }
}
