<?php

namespace App\Http\Controllers\WooCommerce;

use App\Models\Articles\Article;
use App\Http\Controllers\Controller;
use App\APIs\WooCommerce\WooCommerceOrder;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Article::class, 'article');
    }

    public function update(Article $article)
    {
        if (!$article->syncWooCommerce()) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Der Artikel konnte nicht zu WooCommerce hochgeladen werden.',
            ]);
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Der Artikel wurde zu WooCommerce hochgeladen.',
        ]);
    }

    public function show(Article $article)
    {
        $external_id = $article->externalIdsWooCommerce()->whereNotNull('external_id')->first();

        if (!$external_id) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Der Artikel wurde noch nicht zu WooCommerce hochgeladen.',
            ]);
        }

        $response = (new WooCommerceOrder())->deleteProduct($external_id->external_id);

        return $response->json();
    }

    public function destroy(Article $article)
    {
        if (!$article->syncWooCommerceDelete()) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Der Artikel konnte nicht auf WooCommerce gelöscht werden.',
            ]);
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Der Artikel wurde auf WooCommerce gelöscht.',
        ]);
    }
}
