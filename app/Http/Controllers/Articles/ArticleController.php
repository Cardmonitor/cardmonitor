<?php

namespace App\Http\Controllers\Articles;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Games\Game;
use App\Models\Items\Card as ItemCard;
use App\Models\Localizations\Language;
use App\Models\Rules\Rule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ArticleController extends Controller
{
    protected $baseViewPath = 'article';

    public function __construct()
    {
        $this->authorizeResource(Article::class, 'article');
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
            return $user->articles()
                ->select('articles.*')
                ->join('cards', 'cards.id', 'articles.card_id')
                ->condition($request->input('condition_sort'), $request->input('condition_operator'))
                ->expansion($request->input('expansion_id'))
                ->game($request->input('game_id'))
                ->rule($request->input('rule_id'))
                ->isFoil($request->input('is_foil'))
                ->language($request->input('language_id'))
                ->rarity($request->input('rarity'))
                ->unitPrice($request->input('unit_price_min'), $request->input('unit_price_max'))
                ->unitCost($request->input('unit_cost_min'), $request->input('unit_cost_max'))
                ->search($request->input('searchtext'))
                ->sold($request->input('sold'))
                ->storage($request->input('storage_id'))
                ->sync($request->input('sync'))
                ->with([
                    'card.expansion',
                    'language',
                    'rule',
                    'orders',
                    'storage',
                ])
                ->orderBy('cards.name', 'ASC')
                ->paginate();
        }

        $expansions = Expansion::all();

        $languages = Language::all()->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name']];
        });

        return view($this->baseViewPath . '.index')
            ->with('conditions', Article::CONDITIONS)
            ->with('expansions', $expansions)
            ->with('games', Game::keyValue())
            ->with('languages', $languages)
            ->with('rarities', Card::RARITIES)
            ->with('is_applying_rules', $user->is_applying_rules)
            ->with('is_syncing_articles', $user->is_syncing_articles)
            ->with('rules', $user->rules)
            ->with('storages', $user->storages()
                ->withDepth()
                ->defaultOrder()
                ->get());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = auth()->user();
        $defaultCardCosts = ItemCard::defaultCosts($user);

        $expansions = Expansion::all();

        $languages = Language::all()->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name']];
        });

        return view($this->baseViewPath . '.create')
            ->with('conditions', Article::CONDITIONS)
            ->with('defaultCardCosts', $defaultCardCosts)
            ->with('expansions', $expansions)
            ->with('games', Game::keyValue())
            ->with('languages', $languages)
            ->with('storages', auth()->user()->storages()
                ->withDepth()
                ->defaultOrder()
                ->get());
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
            'card_id' => 'required|integer',
            'cardmarket_comments' => 'sometimes|nullable|string',
            'language_id' => 'sometimes|required|integer',
            'storage_id' => 'sometimes|nullable|exists:storages,id',
            'condition' => 'sometimes|required|string',
            // 'bought_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            // 'sold_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            'is_foil' => 'sometimes|required|boolean',
            'is_signed' => 'sometimes|required|boolean',
            'is_playset' => 'sometimes|required|boolean',
            'unit_price_formatted' => 'sometimes|required|formated_number',
            'unit_cost_formatted' => 'sometimes|required|formated_number',
        ]);

        $articles = [];
        for ($i = 0; $i < $request->input('count'); $i++) {
            $article = Article::create($attributes);

            if ($request->input('sync')) {
                $article->syncAdd();
            }

            $article->load([
                'card.expansion',
                'card.localizations',
                'language',
                'orders',
                'storage',
            ]);

            $articles[] = $article;
        }

        return response()->json($articles, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        return view($this->baseViewPath . '.show')
            ->with('model', $article);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function edit(Article $article)
    {
        return view($this->baseViewPath . '.edit')
            ->with('model', $article);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Article $article)
    {
        $storage_id = $request->input('storage_id');

        $article->update($request->validate([
            'cardmarket_comments' => 'sometimes|nullable|string',
            'language_id' => 'sometimes|required|integer',
            'condition' => 'sometimes|required|string',
            'number' => 'sometimes|nullable|string',
            'storage_id' => 'sometimes|nullable|exists:storages,id',
            'slot' => 'sometimes|nullable|integer' . ($storage_id ? '|in:0,' . implode(',', \App\Models\Storages\Storage::openSlots($storage_id, $article->id)) : ''),
            // 'bought_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            // 'sold_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            'is_foil' => 'sometimes|required|boolean',
            'is_signed' => 'sometimes|required|boolean',
            'is_playset' => 'sometimes|required|boolean',
            'unit_price_formatted' => 'sometimes|required|formated_number',
            'unit_cost_formatted' => 'sometimes|required|formated_number',
            'provision_formatted' => 'sometimes|required|formated_number',
            'state' => 'sometimes|required|integer',
            'state_comments' => 'sometimes|nullable|string',
        ]));

        if (count($article->orders)) {
            foreach ($article->orders as $key => $order) {
                $order->calculateProfits()
                    ->save();
            }
        }

        if ($request->input('sync')) {
            $article->sync();
        }

        return $article->load([
            'card.expansion',
            'card.localizations',
            'language',
            'orders',
            'storage',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Article $article)
    {
        if ($isDeletable = $article->isDeletable()) {
            if ($isDeletable = $article->syncDelete()) {
                $article->delete();
            }
        }

        if ($request->wantsJson())
        {
            return [
                'deleted' => $isDeletable,
            ];
        }

        return back();
    }
}
