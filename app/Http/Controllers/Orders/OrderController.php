<?php

namespace App\Http\Controllers\Orders;

use App\Models\Items\Custom;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Support\BackgroundTasks;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    protected $baseViewPath = 'order';

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
                ->orders()
                ->select('orders.*')
                ->search($request->input('searchtext'))
                ->state($request->input('state'))
                ->presale($request->input('presale'))
                ->with([
                    'buyer',
                    'evaluation'
                ])
                ->withCount([
                    'articlesOnHold'
                ])
                ->orderBy('paid_at', 'DESC')
                ->paginate();
        }

        return view($this->baseViewPath . '.index')
            ->with('states', Order::STATES)
            ->with('background_tasks', BackgroundTasks::make()->all());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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

        return view($this->baseViewPath . '.show')
            ->with('customs', Custom::where('user_id', $order->user_id)->get())
            ->with('model', $order->load([
                'articles.language',
                'articles.storage',
                'buyer',
                'evaluation',
                'sales.item',
                'seller',
            ]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Orders\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Orders\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Orders\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
