<?php

namespace App\Http\Controllers\Orders\Picklists;

use App\Models\Orders\Order;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;

class PdfController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $articles = Article::getForPicklist($user->id);
        $order_ids = $articles->pluck('order_id')->unique()->toArray();
        $orders = Order::where('user_id', $user->id)->whereIn('id', $order_ids)->get()->keyBy('id');

        foreach ($articles as $article) {
            $article->order = $orders[$article->order_id];
            $article->order->number = array_search($article->order_id, $order_ids) + 1;
        }

        return \PDF::loadView('order.picklists.pdf', [
            'articles' => $articles,
        ], [], [
            'margin_top' => 10,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
        ])->stream('picklist.pdf');
    }
}
