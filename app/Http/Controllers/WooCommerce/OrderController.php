<?php

namespace App\Http\Controllers\WooCommerce;

use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Importers\Articles\WooCommerceOrderImporter;

class OrderController extends Controller
{
    protected $baseViewPath = 'woocommerce.order';

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

            $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
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

        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $response = $WooCommerce->order($attributes['id']);
        $order = $response['data'];

        WooCommerceOrderImporter::import(auth()->user()->id, $order);

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Bestellung #' . $order['id'] . ' importiert.',
        ]);
    }

    public function show(int $id)
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $response = $WooCommerce->order($id);
        $order = $response['data'];
        $cards = [];

        foreach ($order['line_items'] as $key => $line_item) {

            if (Arr::has($cards, $line_item['sku'])) {
                continue;
            }

            [$cardmarket_product_id, $is_foil] = explode('-', $line_item['sku']);
            $cards[$line_item['sku']] = Card::firstOrImport($cardmarket_product_id);
        }

        return view($this->baseViewPath . '.show', [
            'cards' => $cards,
            'conditions' => \App\Models\Articles\Article::CONDITIONS,
            'order' => $order,
        ]);
    }
}
