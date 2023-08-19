<?php

namespace App\Http\Controllers\Articles;

use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Http\Request;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use App\Models\Expansions\Expansion;
use App\Models\Localizations\Language;
use App\Models\Articles\StoringHistory;

class StoringHistoryController extends Controller
{
    protected $baseViewPath = 'article.storing_history';

    public function __construct()
    {
        $this->authorizeResource(StoringHistory::class, 'storing_history');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request->wantsJson()) {
            return $user->storingHistories()
                ->withCount('articles')
                ->latest()
                ->paginate();
        }

        return view($this->baseViewPath . '.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Articles\StoringHistory  $storing_history
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, StoringHistory $storing_history)
    {
        $user = auth()->user();

        if ($request->wantsJson()) {
            return $storing_history->articles()
                ->select('articles.*')
                ->join('cards', 'cards.id', 'articles.card_id')
                ->filter($request->all())
                ->with('orders', function ($query) {
                    $query->where('is_purchase', false);
                })
                ->with([
                    'card.expansion',
                    'card.localizations',
                    'language',
                ])
                ->orderBy('articles.number', 'ASC')
                ->paginate();
        }

        $expansions = Expansion::all();

        $languages = Language::all()->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name']];
        });

        return view($this->baseViewPath . '.show')
            ->with('model', $storing_history)
            ->with('conditions', Article::CONDITIONS)
            ->with('expansions', $expansions)
            ->with('games', Game::keyValue())
            ->with('languages', $languages)
            ->with('rarities', Card::RARITIES)
            ->with('is_applying_rules', $user->is_applying_rules)
            ->with('is_syncing_articles', $user->is_syncing_articles)
            ->with('rules', $user->rules)
            ->with('storages', $user->storagesForFilter());
    }
}
