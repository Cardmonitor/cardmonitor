<?php

namespace App\Http\Controllers\Orders;

use App\APIs\WooCommerce\Status;
use App\APIs\WooCommerce\WooCommercePurchase;
use App\Models\Items\Custom;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use App\Models\Expansions\Expansion;
use App\Models\Localizations\Language;

class PurchaseController extends Controller
{
    protected $baseViewPath = 'purchase';

    public function __construct()
    {
        $this->authorizeResource(Order::class, 'order');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return auth()->user()
                ->purchases()
                ->select('orders.*')
                ->search($request->input('searchtext'))
                ->state($request->input('state'))
                ->with([
                    'seller'
                ])
                ->withCount([
                    'articlesOnHold'
                ])
                ->orderBy('paid_at', 'DESC')
                ->paginate();
        }

        return view($this->baseViewPath . '.index')
            ->with('states', Status::values());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Orders\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        $order->loadCount('articlesOnHold');

        $lanugages = Language::whereIn('id', [Language::ID_ENGLISH, Language::ID_GERMAN])->orderBy('name')->get();

        return view($this->baseViewPath . '.show')
            ->with('customs', Custom::where('user_id', $order->user_id)->get())
            ->with('model', $order->load([
                'articles.language',
                'articles.storage',
                'buyer',
                'evaluation',
                'sales.item',
                'seller',
            ]))
            ->with('conditions', Article::CONDITIONS)
            ->with('languages', $lanugages)
            ->with('expansions', Expansion::all())
            ->with('states', Status::values());
    }

    public function update(Request $request, Order $order)
    {
        $attributes = $request->validate([
            'state' => 'required|string|in:' . implode(',', Status::values()),
        ]);

        $WooCommerce = new WooCommercePurchase();
        $WooCommerce->updateOrder($order->source_id, [
            'status' => $attributes['state'],
        ]);

        $order->update($attributes);

        return redirect($order->path)
            ->with('status', [
                'type' => 'success',
                'text' => 'Der Ankauf wurde gespeichert.',
            ]);
    }
}
