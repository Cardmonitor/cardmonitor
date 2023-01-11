<?php

namespace App\Http\Controllers\Cardmarket\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

class OrderController extends Controller
{
    protected $CardmarketApi;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return auth()->user();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order = null)
    {
        $user = auth()->user();
        $this->CardmarketApi = $user->cardmarketApi;

        if (! $user->api->isConnected()) {
            abort(404);
        }

        if (is_null($order)) {
            $this->processing($user);
            if ($request->has('state')) {
                $this->syncStateOrders($user, $request->input('state'));
            }
            else {
                $this->syncAllOrders($user);
            }
        }
        else {
            $this->syncOrder($order);
        }

        if ($request->wantsJson()) {
            return;
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Bestellung aktualisiert.',
        ]);
    }

    protected function syncOrder(Order $order) : Order
    {
        $cardmarketOrder = $this->CardmarketApi->order->get($order->cardmarket_order_id);

        return Order::updateOrCreateFromCardmarket($order->user_id, $cardmarketOrder['order'], Order::FORCE_UPDATE_OR_CREATE);
    }

    protected function processing(User $user)
    {
        $user->update([
            'is_syncing_orders' => true,
        ]);
    }

    protected function syncStateOrders(User $user, string $state)
    {
        Artisan::queue('order:sync', [
            'user' => $user->id,
            '--actor' => 'seller',
            '--state' => $state,
        ]);
    }

    protected function syncAllOrders(User $user)
    {
        \App\Jobs\Orders\SyncAll::dispatch($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        $user = auth()->user();
        $this->CardmarketApi = $user->cardmarketApi;

        $cardmarket_order = $this->CardmarketApi->order->get($order->cardmarket_order_id);

        return $cardmarket_order;
    }

}
