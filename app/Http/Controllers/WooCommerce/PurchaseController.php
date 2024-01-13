<?php

namespace App\Http\Controllers\WooCommerce;

use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Importers\Orders\WooCommercePurchaseImporter;

class PurchaseController extends Controller
{
    protected $baseViewPath = 'woocommerce.purchase';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $attributes = $request->validate([
                'page' => 'nullable|integer',
                'status' => 'nullable|string',
            ]);

            $WooCommerce = new \App\APIs\WooCommerce\WooCommercePurchase();
            return $WooCommerce->orders($attributes);
        }

        return view($this->baseViewPath . '.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'id' => 'required|integer',
        ]);

        $WooCommerce = new \App\APIs\WooCommerce\WooCommercePurchase();
        $woocommerce_order_response = $WooCommerce->order($attributes['id']);
        $woocommerce_order = $woocommerce_order_response['data'];

        $order = WooCommercePurchaseImporter::import(auth()->user()->id, $woocommerce_order);

        if ($request->wantsJson()) {
            return $order;
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Bestellung #' . $woocommerce_order['id'] . ' importiert.',
        ]);
    }

    public function show(int $id)
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommercePurchase();
        $response = $WooCommerce->order($id);
        $order = $response['data'];
        $cards = [];

        foreach ($order['line_items'] as $key => $line_item) {

            if (Arr::has($cards, $line_item['sku'])) {
                continue;
            }

            [$cardmarket_product_id, $is_foil] = explode('-', $line_item['sku']);
            $cards[$line_item['sku']] = Card::firstOrImport($cardmarket_product_id);
            $cards[$line_item['sku']]->load([
                'expansion',
            ]);
        }

        return view($this->baseViewPath . '.show', [
            'cards' => $cards,
            'conditions' => \App\Models\Articles\Article::CONDITIONS,
            'order' => $order,
        ]);
    }
}
