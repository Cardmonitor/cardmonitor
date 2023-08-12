<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Models\Items\Custom;
use App\Models\Localizations\Language;
use App\Models\Orders\Order;
use Illuminate\Http\Request;

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
            ->with('states', Order::STATES)
            ->with('is_syncing_orders', auth()->user()->is_syncing_orders);
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
            ->with('languages', $lanugages);
    }
}
