<?php

namespace App\Http\Controllers\Orders\Picklists;

use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class PdfController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $cache_key = 'orders.picklist.' . $user->id . '.article_ids';
        if (Cache::has($cache_key)) {
            $article_ids = Cache::get($cache_key);
        } else {
            $article_ids = [];
        }

        $articles = Article::getForPicklist($user->id, $article_ids);

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
