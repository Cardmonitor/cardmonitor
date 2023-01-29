<?php

namespace App\Http\Controllers\Articles\StoringHistory;

use App\Http\Controllers\Controller;
use App\Models\Articles\StoringHistory;
use Illuminate\Support\Facades\Artisan;

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

        return StoringHistory::getPDF($articles, false)->stream('einlagerung-' . $storing_history->id . '.pdf');
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
