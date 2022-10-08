<?php

namespace App\Http\Controllers\Orders\Picklists;

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

        $grouped_articles = Article::getForPicklist($user->id);

        foreach ($grouped_articles as $grouped_article) {

            $grouped_article->card->download();

            if ($grouped_article->card->hasSkryfallData) {
                continue;
            }

            $grouped_article->card->updateFromSkryfallByCardmarketId($grouped_article->card->cardmarket_product_id);
        }

        return view('order.picklists.index', compact('grouped_articles'));
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'articles_in_orders' => 'required|file',
        ]);

        $user_id = auth()->user()->id;
        $cache_key = 'orders.picklist.' . $user_id . '.article_ids';
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
