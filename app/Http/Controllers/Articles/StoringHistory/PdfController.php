<?php

namespace App\Http\Controllers\Articles\StoringHistory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Articles\StoringHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class PdfController extends Controller
{
    const MAX_ARTICLES_PER_FILE = 100;

    public function show(StoringHistory $storing_history)
    {
        $user = auth()->user();

        $articles = $storing_history->articles()
            ->with([
                'card.expansion',
                'card.localizations',
                'language',
                'orders',
            ])
            ->orderBy('articles.number', 'ASC')
            ->get();

        return $this->getPDF($articles)->stream('einlagerung ' . $storing_history->created_at . '.pdf');
    }

    public function store(StoringHistory $storing_history)
    {
        $path = $this->makePath();

        $zip_path = $path . 'einlagerung ' . $storing_history->created_at . '.zip';
        $zip_archive = new \ZipArchive();
        $zip_archive->open($zip_path, \ZipArchive::CREATE);

        $file_counter = 1;
        $storing_history->articles()
            ->with([
                'card.expansion',
                'card.localizations',
                'language',
                'orders',
            ])
            ->orderBy('articles.number', 'ASC')
            ->chunk(self::MAX_ARTICLES_PER_FILE, function ($articles) use ($storing_history, &$file_counter, &$zip_archive, $path) {
                $file_path = $path . $storing_history->id . '-'. $file_counter .'.pdf';

                $this->getPDF($articles)->save($file_path);
                $zip_archive->addFile($file_path, $file_counter . '.pdf');
                $file_counter++;
            });

        $zip_archive->close();

        // delete all PDF-files
        for ($i = 1; $i < $file_counter; $i++) {
            unlink($path . $storing_history->id . '-'. $i .'.pdf');
        }

        return response()->download($zip_path);

    }

    private function getPDF(Collection $articles)
    {
        $pdf = \PDF::loadView('article.storing_history.pdf', [
            'articles' => $articles,
        ], [], [
            'margin_top' => 10,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        return $pdf;
    }

    private function makePath(): string
    {
        $path = storage_path('app/public/einlagerung/');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}
