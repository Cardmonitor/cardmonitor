<?php

namespace App\Http\Controllers\Orders\Purchases;

use App\Models\Orders\Order;
use App\Http\Controllers\Controller;

class PdfController extends Controller
{
    public function show(Order $order)
    {
        $order->load([
            'articles' => function ($query) {
                $query->with([
                    'card.expansion',
                ]);
            },
        ]);

        $articles_gt_500 = $order->articles->where('unit_cost', '>', 500);

        return \PDF::loadView('purchase.pdf', [
            'order' => $order,
            'articles_gt_500' => $articles_gt_500,
            'last_section' => '',
        ], [], [
            'margin_top' => 10,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
        ])->stream($order->source_id . '.pdf');
    }
}
