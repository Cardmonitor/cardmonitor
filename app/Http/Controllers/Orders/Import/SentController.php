<?php

namespace App\Http\Controllers\Orders\Import;

use App\Enums\ExternalIds\ExternalType;
use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SentController extends Controller
{
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'import_sent_file' => 'required|file',
        ]);

        $CardmarketApi = auth()->user()->cardmarketApi;
        $userId = auth()->user()->id;

        $content = $attributes['import_sent_file']->get();

        $rows = explode("\n", $content);
        $row_count = 0;
        $sent_count = 0;
        foreach ($rows as $row) {
            if ($row_count == 0) {
                $row_count++;
                continue;
            }
            $columns = explode(';', $row);
            $cardmarket_order_id = Arr::get($columns, 0, 0);
            $tracking_number = Arr::get($columns, 1, '');

            if (!$cardmarket_order_id) {
                continue;
            }

            $order = Order::where('user_id', $userId)
                ->where('source_slug)', ExternalType::CARDMARKET->value)
                ->where('source_id', $cardmarket_order_id)
                ->first();

            $row_count++;
            if (is_null($order)) {
                continue;
            }

            try {
                if ($tracking_number) {
                    $CardmarketApi->order->setTrackingNumber($order->source_id, $tracking_number);
                    $order->update([
                        'tracking_number' => $tracking_number,
                    ]);
                }
                $cardmarketOrder = $CardmarketApi->order->send($order->source_id);
                $order->updateOrCreateFromCardmarket($userId, $cardmarketOrder['order']);
            }
            catch (\Exception $exc) {
                continue;
            }

            $sent_count++;
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => $sent_count . '/' . ($row_count - 1) . ' Bestellung als versendet markiert.',
        ]);
    }
}
