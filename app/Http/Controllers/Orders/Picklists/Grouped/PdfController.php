<?php

namespace App\Http\Controllers\Orders\Picklists\Grouped;

use App\Models\Articles\Article;
use App\Http\Controllers\Controller;

class PdfController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $grouped_articles = Article::getForGroupedPicklist($user->id);

        return \PDF::loadView('order.picklists.grouped.pdf', [
            'grouped_articles' => $grouped_articles,
        ], [], [
            'margin_top' => 10,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
        ])->stream('picklist-grouped.pdf');
    }
}
