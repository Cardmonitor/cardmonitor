<?php

namespace App\Http\Controllers\Articles;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use Illuminate\Http\Request;

class NumberController extends Controller
{
    protected $baseViewPath = 'article';

    public function __construct()
    {
        $this->authorizeResource(Article::class, 'article');
    }

    public function index()
    {
        $user = auth()->user();
        $max_number = Article::maxNumber($user->id);

        return [
            'number' => Article::incrementNumber($max_number),
        ];
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

        $article->update($request->validate([
            'number' => 'required|nullable|string',
        ]));

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
}
