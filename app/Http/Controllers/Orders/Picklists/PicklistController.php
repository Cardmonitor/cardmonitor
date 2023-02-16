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
    public function index()
    {
        $user = auth()->user();

        $articles = Article::getForPicklist($user->id);
        $order_ids = $articles->pluck('order_id')->unique()->toArray();
        sort($order_ids); // reset keys
        $orders = Order::where('user_id', $user->id)->whereIn('id', $order_ids)->get()->keyBy('id');

        foreach ($articles as $article) {

            $article->card->download();

            $article->order = $orders[$article->order_id];
            $article->order->number = (array_search($article->order_id, $order_ids) + 1);

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
