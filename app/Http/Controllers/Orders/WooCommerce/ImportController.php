<?php

namespace App\Http\Controllers\Orders\WooCommerce;

use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Importers\Orders\WooCommerceOrderImporter;
use Illuminate\Support\Facades\Artisan;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        // Mehrere Bestellungen importieren
    }

    public function show(Order $order)
    {
        if (!$order->is_woocommerce) {
            return back()->with('status', [
                'type' => 'error',
                'text' => 'Bestellung nicht von WooCommerce.',
            ]);
        }

        Artisan::call('order:woocommerce:import', [
            'user' => $order->user_id,
            '--order' => $order->source_id,
        ]);

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Bestellung aktualisiert.',
        ]);
    }
}
