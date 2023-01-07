<?php

namespace App\Http\Controllers\Articles\StoringHistory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Articles\StoringHistory;

class PdfController extends Controller
{
    public function show(Request $request, StoringHistory $storing_history)
    {
        $user = auth()->user();

        $articles = $storing_history->articles()
            ->filter($request->all())
            ->with([
                'card.expansion',
                'card.localizations',
                'language',
                'orders',
            ])
            ->orderBy('articles.number', 'ASC')
            ->get();

        return \PDF::loadView('article.storing_history.pdf', [
            'articles' => $articles,
        ], [], [
            'margin_top' => 10,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
        ])->stream('einlagerung ' . $storing_history->created_at_formatted . '.pdf');
    }
}
