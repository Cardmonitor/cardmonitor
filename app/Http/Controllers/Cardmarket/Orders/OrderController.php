<?php

namespace App\Http\Controllers\Cardmarket\Orders;

use App\User;
use App\Enums\Orders\Status;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class OrderController extends Controller
{
    protected $CardmarketApi;

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return auth()->user();
        }
    }

    public function update(Request $request, Order $order = null)
    {
        $request->validate([
            'state' => 'sometimes|nullable|in:' . implode(',', Status::values()),
        ]);

        $user = auth()->user();
        $this->CardmarketApi = $user->cardmarketApi;

        if (! $user->api->isConnected()) {
            abort(404);
        }

        if (is_null($order)) {
            $this->processing($user);
            if ($request->has('state') && in_array($request->input('state'), Status::values())) {
                $state = $request->input('state');
                $this->syncStateOrders($user, $state);
            }
            else {
                $this->syncAllOrders($user);
            }

            return;
        }

        $this->syncOrder($order);

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
        $states = [$state];
        if ($state == Status::PAID->value) {
            $states[] = Status::BOUGHT->value;
        }

        Artisan::queue('order:sync', [
            'user' => $user->id,
            '--actor' => 'seller',
            '--states' => $states,
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
