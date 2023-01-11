<?php

namespace App\Http\Controllers\Cardmarket\Articles;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\User;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    protected $CardmarketApi;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return auth()->user();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Article $article = null)
    {
        $user = auth()->user();
        $this->CardmarketApi = $user->cardmarketApi;

        if (! $user->api->isConnected()) {
            abort(404);
        }

        if (is_null($article)) {
            $this->syncAll($user);
        }
        else {
            $this->sync($article);
        }

        if ($request->wantsJson()) {
            return;
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Artikel aktualisiert.',
        ]);
    }

    protected function sync(Article $article) : Article
    {
        dump('syncing one..');
    }

    protected function syncAll(User $user)
    {
        $user->update([
            'is_syncing_articles' => true,
        ]);
        \App\Jobs\Articles\SyncAll::dispatch($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Articles\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        $user = auth()->user();
        $this->CardmarketApi = $user->cardmarketApi;

        $cardmarket_article = $this->CardmarketApi->stock->article($article->cardmarket_article_id);

        return $cardmarket_article;
    }
}
