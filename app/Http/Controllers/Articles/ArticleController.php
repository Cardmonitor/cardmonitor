<?php

namespace App\Http\Controllers\Articles;

use App\User;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Articles\Article;
use App\Support\BackgroundTasks;
use App\Http\Controllers\Controller;
use App\Models\Expansions\Expansion;
use App\Models\Items\Card as ItemCard;
use App\Models\Localizations\Language;
use App\Console\Commands\Article\Imports\Cardmarket\StockfileCommand;
use App\Enums\ExternalIds\ExternalType;

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
                ->useIndex('articles_user_id_is_sellable_index')
                ->join('cards', 'cards.id', 'articles.card_id')
                ->filter($request->all())
                ->with('card', function ($query) {
                    $query->with([
                        'expansion',
                        'localizations',
                    ]);
                })
                ->with('orders', function ($query) {
                    $query->where('is_purchase', false);
                })
                ->with([
                    'externalIdsCardmarket',
                    'externalIdsWooCommerce',
                ])
                ->with([
                    'language',
                    'rule',
                    'storage',
                ])
                ->orderBy('articles.card_name', 'ASC')
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
            ->with('storages', $user->storagesForFilter())
            ->with('log_file_exists', StockfileCommand::zipArchiveExists($user))
            ->with('background_tasks', BackgroundTasks::make()->all());
    }

    private function getLogFileUrl(User $user): ?string
    {
        if (!file_exists(StockfileCommand::zipArchivePath($user))) {
            return null;
        }

        return StockfileCommand::zipArchiveUrl($user);
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
            'card_id' => 'nullable|integer',
            'count' => 'required|integer|min:1',
            'cardmarket_comments' => 'sometimes|nullable|string',
            'language_id' => 'sometimes|required|integer',
            'storage_id' => 'sometimes|nullable|exists:storages,id',
            'condition' => 'sometimes|required|string',
            // 'bought_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            // 'sold_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            'is_foil' => 'sometimes|required|boolean',
            'is_reverse_holo' => 'sometimes|required|boolean',
            'is_first_edition' => 'sometimes|required|boolean',
            'is_signed' => 'sometimes|required|boolean',
            'is_playset' => 'sometimes|required|boolean',
            'unit_price_formatted' => 'sometimes|required|formated_number',
            'unit_cost_formatted' => 'sometimes|required|formated_number',
            'order_id' => 'sometimes|required|integer',
            'local_name' => 'sometimes|required|string',
        ]);

        if (! $request->has('order_id')) {
            $attributes['is_sellable_since'] = now();
        }

        $articles = [];
        for ($i = 0; $i < $request->input('count'); $i++) {
            $article = Article::create($attributes);

            if ($attributes['order_id'] ?? false) {
                $article->orders()->attach($attributes['order_id']);
            }

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

            if (count($article->orders)) {
                foreach ($article->orders as $order) {
                    $order->calculateProfits()
                        ->save();
                }
            }

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
        $article->load([
            'externalIdsCardmarket',
            'externalIdsWooCommerce',
        ]);

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
        $languages = Language::all()->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name']];
        });

        return view($this->baseViewPath . '.edit')
            ->with('model', $article)
            ->with('conditions', Article::CONDITIONS)
            ->with('languages', $languages)
            ->with('storages', auth()->user()->storages()
                ->withDepth()
                ->defaultOrder()
                ->get());
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
            'cardmarket_article_id' => 'sometimes|nullable|integer',
            'cardmarket_comments' => 'sometimes|nullable|string',
            'language_id' => 'sometimes|required|integer',
            'condition' => 'sometimes|required|string',
            'card_id' => 'sometimes|required|integer',
            'number' => 'sometimes|nullable|string',
            'storage_id' => 'sometimes|nullable|exists:storages,id',
            'slot' => 'sometimes|nullable|integer' . ($storage_id ? '|in:0,' . implode(',', \App\Models\Storages\Storage::openSlots($storage_id, $article->id)) : ''),
            // 'bought_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            // 'sold_at_formatted' => 'required|date_format:"d.m.Y H:i"',
            'is_foil' => 'sometimes|required|boolean',
            'is_reverse_holo' => 'sometimes|required|boolean',
            'is_first_edition' => 'sometimes|required|boolean',
            'is_reverse_holo' => 'sometimes|required|boolean',
            'is_first_edition' => 'sometimes|required|boolean',
            'is_signed' => 'sometimes|required|boolean',
            'is_playset' => 'sometimes|required|boolean',
            'unit_price_formatted' => 'sometimes|required|formated_number',
            'unit_cost_formatted' => 'sometimes|required|formated_number',
            'provision_formatted' => 'sometimes|required|formated_number',
            'state' => 'sometimes|nullable|integer',
            'state_comments' => 'sometimes|nullable|string',
            'local_name' => 'sometimes|nullable|string',
        ]));

        if (count($article->orders)) {
            foreach ($article->orders as $key => $order) {
                $order->calculateProfits()
                    ->save();
            }
        }

        $is_synced = true;
        if ($request->input('sync')) {
            $is_synced = $article->sync();
        }

        $article->load([
            'card.expansion',
            'card.localizations',
            'language',
            'orders',
            'storage',
        ]);

        return response()->json($article, $is_synced ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Article $article)
    {
        if ($is_deletable = $article->isDeletable()) {
            foreach ($article->externalIds()->whereNotNull('external_id')->get() as $external_id) {
                $is_deletable = match ($external_id->external_type) {
                    ExternalType::CARDMARKET->value => $article->syncDelete(),
                    ExternalType::WOOCOMMERCE->value => $article->syncWooCommerceDelete(),
                };

                if (!$is_deletable) {
                    break;
                }
            }

            // Fallback, wenn es keine external_id gibt
            if ($article->cardmarket_article_id) {
                $is_deletable = $article->syncDelete();
            }

            if ($is_deletable) {
                $article->delete();
            }
        }

        if ($request->wantsJson())
        {
            return [
                'deleted' => $is_deletable,
            ];
        }

        return redirect()->route('article.index');
    }
}
