<?php

namespace App\Http\Controllers\Cardmarket\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SendController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $CardmarketApi = auth()->user()->cardmarketApi;

        try {
            $cardmarketOrder = $CardmarketApi->order->send($order->cardmarket_order_id);
            $order = $order->updateOrCreateFromCardmarket(auth()->user()->id, $cardmarketOrder['order']);

            if ($request->wantsJson()) {
                return $order->load([
                    'buyer',
                    'evaluation',
                ]);
            }

            return back()->with('status', [
                'type' => 'success',
                'text' => 'Bestellung als versendet markiert.',
            ]);
        }
        catch (ClientException $exc) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Bestellung konnte nicht als versendet markiert werden.',
            ]);
        }
    }
}
