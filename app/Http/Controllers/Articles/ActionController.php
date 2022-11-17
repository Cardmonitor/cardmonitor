<?php

namespace App\Http\Controllers\Articles;

use App\User;
use Carbon\Carbon;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use App\Models\Rules\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use App\Models\Expansions\Expansion;
use App\Models\Items\Card as ItemCard;
use App\Models\Localizations\Language;
use App\Models\Storages\Storage;
use Illuminate\Database\Eloquent\Collection;

class ActionController extends Controller
{
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
                ->select('articles.*')
                ->join('cards', 'cards.id', 'articles.card_id')
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
            case 'setNumber': $message = $this->setNumber($user, $articles); break;
            case 'resetNumber': $message = $this->resetNumber($articles); break;
            case 'setStorage': $message = $this->setStorage($articles, $attributes['storage_id']); break;
            case 'resetStorage': $message = $this->resetStorage($articles); break;
            case 'syncCardmarket': $message = $this->syncCardmarket($articles); break;

            default: $message = 'Aktion nicht verfügbar'; break;
        }

        return [
            'message' => $message,
            'arcicles_count' => $articles->count(),
        ];
    }

    private function setNumber(User $user, Collection $articles): string
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

        return 'Nummern bei ' . $articles_count . ' Artikeln gesetzt. Letzte Nummer: ' . $number;
    }

    private function resetNumber(Collection $articles): string
    {
        $articles->each(function ($article) {
            $article->update([
                'number' => null,
            ]);
        });

        return 'Nummern bei ' . $articles->count() . ' Artikeln entfernt.';
    }

    private function setStorage(Collection $articles, int $storage_id): string
    {
        $storage = Storage::find($storage_id);

        $articles->each(function ($article) use ($storage) {
            $article->setStorage($storage)->save();
        });

        return 'Lagerplatz bei ' . $articles->count() . ' Artikeln gesetzt.';
    }

    private function resetStorage(Collection $articles): string
    {
        $articles->each(function ($article) {
            $article->unsetStorage()->save();
        });

        return 'Lagerplatz bei ' . $articles->count() . ' Artikeln entfernt.';
    }

    private function syncCardmarket(Collection $articles): string
    {
        $articles->each(function ($article) {
            $article->sync();
        });

        return $articles->count() . ' zu Cardmarket hochgeladen.';
    }
}
