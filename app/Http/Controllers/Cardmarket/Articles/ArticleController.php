<?php

namespace App\Http\Controllers\Cardmarket\Articles;

use App\User;
use Illuminate\Http\Request;
use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use App\Support\Users\CardmarketApi;

class ArticleController extends Controller
{
    protected CardmarketApi $CardmarketApi;

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return auth()->user();
        }
    }

    public function update(Article $article)
    {
        $user = auth()->user();
        $this->CardmarketApi = $user->cardmarketApi;

        if (! $user->api->isConnected()) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Es ist kein Cardmarket Konto verbunden.',
            ]);
        }

        if (! $article->can_upload_to_cardmarket) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Es ist keine Artikelnummer vorhanden.',
            ]);
        }

        $is_synced = $article->sync();
        if (! $is_synced) {
            if ($article->sync_error) {
                return back()->with('status', [
                    'type' => 'danger',
                    'text' => $article->sync_error,
                ]);
            }

            return back()->with('status', [
                'type' => 'warning',
                'text' => 'Die Karte wurde nicht aktualisiert und auf den Stand von Cardmarket gebracht.',
            ]);
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Der Artikel wurde zu Cardmarket hochgeladen.',
        ]);
    }

    public function show(Article $article)
    {
        $user = auth()->user();
        $this->CardmarketApi = $user->cardmarketApi;

        $cardmarket_article = $this->CardmarketApi->stock->article($article->cardmarket_article_id);

        return $cardmarket_article;
    }

    public function destroy(Article $article)
    {
        if (!$article->syncDelete()) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Der Artikel konnte nicht auf Cardmarket gelöscht werden.',
            ]);
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Der Artikel wurde auf Cardmarket gelöscht.',
        ]);
    }
}
