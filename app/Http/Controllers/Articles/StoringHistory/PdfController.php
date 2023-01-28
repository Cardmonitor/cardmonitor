<?php

namespace App\Http\Controllers\Articles\StoringHistory;

use App\Http\Controllers\Controller;
use App\Models\Articles\StoringHistory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Collection;

class PdfController extends Controller
{
    public function show(StoringHistory $storing_history)
    {
        $articles = $storing_history->articles()
            ->with([
                'card.expansion',
                'card.localizations',
                'language',
                'orders',
            ])
            ->orderBy('articles.number', 'ASC')
            ->get();

        return $this->getPDF($articles)->stream('einlagerung-' . $storing_history->id . '.pdf');
    }

    private function getPDF(Collection $articles)
    {
        return \PDF::loadView('article.storing_history.pdf', [
            'articles' => $articles,
            'use_image_storage_path' => false,
        ], [], [
            'margin_top' => 10,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);
    }

    public function store(StoringHistory $storing_history)
    {
        Artisan::queue('storing-history:exports:pdf', [
            'id' => $storing_history->id,
        ]);

        return back()->with('status', [
            'type' => 'success',
            'text' => 'PDFs werden im Hintergrund zu Dropbox exportiert.',
        ]);
    }
}
