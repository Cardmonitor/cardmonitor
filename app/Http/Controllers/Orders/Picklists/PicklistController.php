<?php

namespace App\Http\Controllers\Orders\Picklists;

use App\Models\Orders\Order;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;

class PicklistController extends Controller
{
    public function index(string $view = null)
    {
        $user = auth()->user();

        $articles = Article::getForPicklist($user->id);
        $orders = Order::getForPicklist($user->id);
        $sorted_order_ids = $orders->pluck('id')->toArray();

        foreach ($articles as $article) {

            // Bilder nur für view laden
            if (is_null($view)) {
                $article->card->download();
            }

            $article->order = $orders[$article->order_id];
            $article->order->number = (array_search($article->order_id, $sorted_order_ids) + 1);

        }

        if ($view === 'pdf') {
            return \PDF::loadView('order.picklists.pdf', [
                'articles' => $articles,
                'last_section' => '',
            ], [], [
                'margin_top' => 10,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_bottom' => 10,
                'margin_header' => 0,
                'margin_footer' => 0,
            ])->stream('picklist.pdf');
        }

        return view('order.picklists.index')
            ->with('articles', $articles);
    }
}
