<?php

namespace App\Http\Controllers\Orders\Articles;

use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Order::class, 'order');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        $order->articles()->update($request->validate([
            'state' => 'sometimes|nullable|integer',
            'state_comments' => 'sometimes|nullable|string',
        ]));

        $order->loadCount('articlesOnHold');

        return back()->with('status', [
            'type' => 'success',
            'text' => $order->articles_on_hold_count === 0 ? 'Bestellung kann wieder gepickt werden' : 'Bestellung wurde zurückgestellt.',
        ]);
    }
}
