<?php

namespace App\Http\Controllers\Orders\Articles;

use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Order::class, 'order');
    }

    public function index(Request $request, Order $order)
    {
        return $order->articles()->with([
            'language',
            'storage',
        ])->get();
    }
}
