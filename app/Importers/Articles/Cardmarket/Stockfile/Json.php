<?php

namespace App\Importers\Articles\Cardmarket\Stockfile;

use Generator;
use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Collection;

class Json
{
    private array $cardmarket_cards = [];
    private Collection $expansions;

    public function __construct(private int $user_id, private string $relative_file_path = '') {}

    public function download(): string
    {
        $user = \App\User::findOrFail($this->user_id);
        $response = $user->cardmarketApi->stock_export->get();
        $stock_exports = $response['stockExports'];
        foreach ($stock_exports as $stock_export) {
            if ($stock_export['status'] === 'finished') {
                break;
            }
        }

        $response = Http::get($stock_export['url']);
        $relative_path = 'articles/stock/';
        $this->relative_file_path = $relative_path . $stock_export['resourceId'];
        \Illuminate\Support\Facades\Storage::put($this->relative_file_path, $response->body());

        return storage_path('app/' . $this->relative_file_path);
    }

    public function parse(): Generator
    {
        // TODO: https://github.com/halaxa/json-machine

        $cardmarket_articles = json_decode(\Illuminate\Support\Facades\Storage::get($this->relative_file_path), true);
        foreach ($cardmarket_articles['article'] as $cardmarket_article) {
            yield $cardmarket_article;
        }
    }

    public function setCardmarketCards(array $shoppingcart_articles = []): array
    {
        $this->setExpansions();
        $no_storage = Storage::noStorage($this->user_id)->first();
        $no_storage_id = $no_storage->id ?? null;

        foreach ($this->parse() as $cardmarket_article_json) {

            // OVP acrticles are not needed
            if (! Arr::has($cardmarket_article_json, 'product.idExpansion')) {
                continue;
            }

            $this->ensureExpansionExists($cardmarket_article_json);

            Card::firstOrImport($cardmarket_article_json['idProduct']);

            $cardmarket_article = $this->cardmarketArticleJsonToCardmarketArticle($cardmarket_article_json);

            $cardmarket_article['user_id'] = $this->user_id;
            $cardmarket_article['storage_id'] = $no_storage_id;
            $cardmarket_article['unit_cost'] = \App\Models\Items\Card::defaultPrice($this->user_id, '');
            $cardmarket_article['exported_at'] = now();

            if (! Arr::has($this->cardmarket_cards, $cardmarket_article['card_id'])) {
                $this->cardmarket_cards[$cardmarket_article['card_id']] = [
                    'articles' => [],
                    'amount' => 0,
                ];
            }

            $this->cardmarket_cards[$cardmarket_article['card_id']]['articles'][$cardmarket_article['cardmarket_article_id']] = $cardmarket_article;
            $this->cardmarket_cards[$cardmarket_article['card_id']]['amount'] += $cardmarket_article['amount'];
        }

        $this->addArticlesInShoppingcart($shoppingcart_articles);

        $this->saveInFile();

        return $this->cardmarket_cards;
    }

    private function ensureExpansionExists(array $cardmarket_article_json): void
    {
        if (Arr::has($this->expansions, $cardmarket_article_json['product']['idExpansion'])) {
            return;
        }

        $card = Card::import($cardmarket_article_json['idProduct']);

        Artisan::queue('expansion:import', [
            'expansion' => $card->expansion_id
        ]);

        $this->setExpansions();
    }

    private function setExpansions(): void
    {
        $this->expansions = Expansion::query()->get()->keyBy('id');
    }

    private function addArticlesInShoppingcart(array $shoppingcart_articles)
    {
        foreach ($shoppingcart_articles as $shoppingcart_article) {

            if (! Arr::has($this->cardmarket_cards, $shoppingcart_article['idProduct'])) {
                $this->cardmarket_cards[$shoppingcart_article['idProduct']] = [
                    'articles' => [],
                    'amount' => 0,
                ];
            }

            if (Arr::has($this->cardmarket_cards[$shoppingcart_article['idProduct']]['articles'], $shoppingcart_article['idArticle'])) {
                $this->cardmarket_cards[$shoppingcart_article['idProduct']]['articles'][$shoppingcart_article['idArticle']]['amount'] += $shoppingcart_article['count'];
            }
            else {
                $this->cardmarket_cards[$shoppingcart_article['idProduct']]['articles'][$shoppingcart_article['idArticle']] = $this->cardmarketArticleJsonToCardmarketArticle($shoppingcart_article);
            }

            $this->cardmarket_cards[$shoppingcart_article['idProduct']]['amount'] += $shoppingcart_article['count'];
        }
    }

    private function cardmarketArticleJsonToCardmarketArticle(array $cardmarket_article_json): array
    {
        return [
            'amount' => Arr::get($cardmarket_article_json, 'count'),
                'card_id' => Arr::get($cardmarket_article_json, 'idProduct'),
                'cardmarket_article_id' => Arr::get($cardmarket_article_json, 'idArticle'),
                'cardmarket_comments' => Arr::get($cardmarket_article_json, 'comments'),
                'condition' => Arr::get($cardmarket_article_json, 'condition'),
                'has_sync_error' => false,
                'is_altered' => Arr::get($cardmarket_article_json, 'isAltered', false),
                'is_first_edition' => Arr::get($cardmarket_article_json, 'isFirstEd', false),
                'is_foil' => Arr::get($cardmarket_article_json, 'isFoil', false),
                'is_in_shoppingcard' => Arr::get($cardmarket_article_json, 'inShoppingCart', false),
                'is_playset' => Arr::get($cardmarket_article_json, 'isPlayset', false),
                'is_reverse_holo' => Arr::get($cardmarket_article_json, 'isReverseHolo', false),
                'is_signed' => Arr::get($cardmarket_article_json, 'isSigned', false),
                'language_id' => Arr::get($cardmarket_article_json, 'language.idLanguage'),
                'number_from_cardmarket_comments' => Article::numberFromCardmarketComments(Arr::get($cardmarket_article_json, 'comments')),
                'sold_at' => null,
                'sync_error' => null,
                'unit_price' => Arr::get($cardmarket_article_json, 'price'),
        ];
    }

    private function saveInFile(): void
    {
        if (! is_dir($directory = storage_path('app/imports/cardmarket'))) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(storage_path('app/imports/cardmarket/' . $this->user_id . '-stockfile.json'), json_encode($this->cardmarket_cards));
    }
}