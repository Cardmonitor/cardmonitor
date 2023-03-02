<?php

namespace App\Http\Controllers\Orders\Picklists;

use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Importers\Orders\ArticlesInOrdersCsvImporter;

class PicklistController extends Controller
{
    public function index(string $view = null)
    {
        $user = auth()->user();

        $articles = Article::getForPicklist($user->id);
        $orders = Order::getForPicklist($articles, $user->id);
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

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'articles_in_orders' => 'required|file',
        ]);

        $user_id = auth()->user()->id;
        $cache_key = 'orders.picklist.grouped.' . $user_id . '.article_ids';
        Cache::forget($cache_key);

        $orders = ArticlesInOrdersCsvImporter::importFromFilePath($user_id, $attributes['articles_in_orders']->path());
        $orders_count = count($orders);

        $article_ids = [];
        foreach ($orders as $order) {
            $article_ids = array_merge($article_ids, $order->articles->pluck('id')->toArray());
        }

        Cache::forever($cache_key, $article_ids);

        return back()->with('status', 'status', [
            'type' => 'success',
            'text' => $orders_count . ' ' . ($orders_count == 1 ? 'Bestellung' : 'Bestellungen') . ' wurden importiert.',
        ]);
    }
}
