<?php

namespace App\Http\Controllers\WooCommerce;

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
}
