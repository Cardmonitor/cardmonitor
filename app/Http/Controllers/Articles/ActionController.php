<?php

namespace App\Http\Controllers\Articles;

use App\User;
use Illuminate\Http\Request;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use App\Models\Articles\StoringHistory;
use App\Models\Storages\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class ActionController extends Controller
{
    private string $message = '';
    private $model = null;

    public function __construct()
    {
        $this->authorizeResource(Article::class, 'article');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'articles' => 'required|string',
            'action' => 'required|string',
            'storage_id' => 'required|nullable|integer',
            'filter' => 'required|array',
        ]);

        $user = auth()->user();

        $articles = $user->articles()
                ->filter($attributes['filter'])
                ->with([
                    'card.expansion',
                    'language',
                    'rule',
                    'orders',
                    'storage',
                ])
                ->orderBy('cards.name', 'ASC')
                ->get();

        switch ($attributes['action']) {
            case 'setNumber': $this->setNumber($user, $articles); break;
            case 'resetNumber': $this->resetNumber($articles); break;
            case 'setStorage': $this->setStorage($articles, $attributes['storage_id']); break;
            case 'resetStorage': $this->resetStorage($articles); break;
            case 'syncCardmarket': $this->syncCardmarket($articles); break;
            case 'storing': $this->storing($user, $articles); break;

            default: $message = 'Aktion nicht verfügbar'; break;
        }

        return [
            'message' => $this->message,
            'arcicles_count' => $articles->count(),
            'model' => $this->model
        ];
    }

    private function setNumber(User $user, Collection $articles): void
    {
        $articles_count = 0;
        $number = Article::maxNumber($user->id);

        $articles->each(function ($article) use (&$number, &$articles_count) {
            if ($article->number) {
                return;
            }
            $number = Article::incrementNumber($number);
            $article->update([
                'number' => $number,
            ]);
            $articles_count++;
        });

        $this->message = 'Nummern bei ' . $articles_count . ' Artikeln gesetzt. Letzte Nummer: ' . $number;
    }

    private function resetNumber(Collection $articles): void
    {
        $articles->each(function ($article) {
            $article->update([
                'number' => null,
            ]);
        });

        $this->message = 'Nummern bei ' . $articles->count() . ' Artikeln entfernt.';
    }

    private function setStorage(Collection $articles, int $storage_id): void
    {
        $storage = Storage::find($storage_id);

        $articles->each(function ($article) use ($storage) {
            $article->setStorage($storage)->save();
        });

        $this->message = 'Lagerplatz bei ' . $articles->count() . ' Artikeln gesetzt.';
    }

    private function resetStorage(Collection $articles): void
    {
        $articles->each(function ($article) {
            $article->unsetStorage()->save();
        });

        $this->message = 'Lagerplatz bei ' . $articles->count() . ' Artikeln entfernt.';
    }

    private function syncCardmarket(Collection $articles): void
    {
        $articles->each(function ($article) {
            $article->sync();
        });

        $this->message = $articles->count() . ' zu Cardmarket hochgeladen.';
    }

    private function storing(User $user, Collection $articles): void
    {
        $storing_history = $user->storingHistories()->create();

        $articles->each(function ($article) use ($storing_history) {
            $article->update([
                'storing_history_id' => $storing_history->id,
            ]);
        });

        Artisan::queue('article:exports:dropbox', [
            'user_id' => $user->id,
        ]);

        $this->message = $articles->count() . ' Artikel Eingelagert.';
        $this->model = $storing_history;
    }
}
