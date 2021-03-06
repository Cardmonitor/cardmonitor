<?php

namespace App\Http\Controllers;

use App\Models\Orders\Evaluation;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        $cardmarketIsConnected = $user->api->isConnected();
        if ($cardmarketIsConnected) {
            $cardmarketAccount = $user->cardmarketApi->account->get();
            $cardmarketAccount = $cardmarketAccount['account'];
            $cardmarketConnectLink = '';
        }
        else {
            $cardmarketAccount = [];
            $cardmarketConnectLink = (App::make('CardmarketApi'))->access->link();
        }

        $paidOrders = Order::where('user_id', $user->id)
            ->with('buyer')
            ->where('state', 'paid')
            ->get();

        $evaluations = Evaluation::join('orders', 'orders.id', '=', 'evaluations.order_id')
            ->with('order.buyer')
            ->where('orders.user_id', $user->id)
            ->orderBy('orders.received_at', 'DESC')
            ->limit(5)
            ->get();

        $orders = DB::table('orders')->select('state', DB::raw('COUNT(*) AS count'))->groupBy('state')->get();
        $ordersByState = [];
        foreach ($orders as $key => $order) {
            $ordersByState[$order->state] = $order->count;
        }

        return view('home')
            ->with('cardmarketAccount', $cardmarketAccount)
            ->with('cardmarketConnectLink', $cardmarketConnectLink)
            ->with('evaluations', $evaluations)
            ->with('invalid_at', $user->api->invalid_at)
            ->with('ordersByState', $ordersByState)
            ->with('paidOrders', $paidOrders)
            ->with('paidOrders_count', count($paidOrders))
            ->with('api', $user->api)
            ->with('is_syncing_articles', $user->is_syncing_articles)
            ->with('is_syncing_orders', $user->is_syncing_orders);
    }
}
