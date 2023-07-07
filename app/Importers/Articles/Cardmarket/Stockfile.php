<?php

namespace App\Importers\Articles\Cardmarket;

use App\User;
use Generator;
use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Collection;
use App\Transformers\Articles\Csvs\Transformer;

class Stockfile
{
    private string $path;
    private int $user_id;
    private int $game_id;
    private array $cardmarket_cards = [];
    private Collection $expansions;

    public function __construct(int $user_id, string $path, int $game_id)
    {
        $this->user_id = $user_id;
        $this->path = $path;
        $this->game_id = $game_id;
    }

    public function download(): string
    {
        $user = User::with('api')->find($this->user_id);

        if (is_null($user)) {
            return '';
        }

        $filename = $user->cardmarketApi->downloadStockFile($user->id, $this->game_id);
        $this->path = storage_path('app/' . $filename);

        return $this->path;
    }

    public function parse(): Generator
    {
        $file = fopen($this->path, 'r');

        while (($line = fgetcsv($file, 2000, ';')) !== false) {
            yield $line;
        }

        fclose($file);
    }

    public function setCardmarketCards(array $shoppingcart_articles = []): array
    {
        $this->setExpansions();
        $no_storage = Storage::noStorage($this->user_id)->first();
        $no_storage_id = $no_storage->id ?? null;

        $row_count = 0;
        foreach ($this->parse() as $stock_row) {
            if ($row_count === 0) {
                $row_count++;
                continue;
            }

            $this->ensureExpansionExists($stock_row);

            Card::firstOrImport($stock_row[Article::CSV_CARDMARKET_PRODUCT_ID]);

            $cardmarket_article = Transformer::transform($this->game_id, $stock_row);

            $cardmarket_article['user_id'] = $this->user_id;
            $cardmarket_article['storage_id'] = $no_storage_id;
            $cardmarket_article['unit_cost'] = \App\Models\Items\Card::defaultPrice($this->user_id, '');
            $cardmarket_article['exported_at'] = now();

            if (! Arr::has($this->cardmarket_cards, $stock_row[Article::CSV_CARDMARKET_PRODUCT_ID])) {
                $this->cardmarket_cards[$stock_row[Article::CSV_CARDMARKET_PRODUCT_ID]] = [
                    'articles' => [],
                    'amount' => 0,
                ];
            }

            $this->cardmarket_cards[$stock_row[Article::CSV_CARDMARKET_PRODUCT_ID]]['articles'][$stock_row[Article::CSV_CARDMARKET_ARTICLE_ID]] = $cardmarket_article;
            $this->cardmarket_cards[$stock_row[Article::CSV_CARDMARKET_PRODUCT_ID]]['amount'] += $stock_row[Article::CSV_AMOUNT[$this->game_id]];
        }

        $this->addArticlesInShoppingcart($shoppingcart_articles);

        $this->saveInFile();

        return $this->cardmarket_cards;
    }

    private function ensureExpansionExists(array $stock_row): void
    {
        if (Arr::has($this->expansions, $stock_row[4])) {
            return;
        }

        $card = Card::import($stock_row[Article::CSV_CARDMARKET_PRODUCT_ID]);

        Artisan::queue('expansion:import', [
            'expansion' => $card->expansion_id
        ]);

        $this->setExpansions();
    }

    private function setExpansions(): void
    {
        $this->expansions = Expansion::where('game_id', $this->game_id)->get()->keyBy('abbreviation');
    }

    private function addArticlesInShoppingcart(array $shoppingcart_articles)
    {
        foreach ($shoppingcart_articles as $shoppingcart_article) {

            if ($shoppingcart_article['product']['idGame'] !== $this->game_id) {
                continue;
            }

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
                $this->cardmarket_cards[$shoppingcart_article['idProduct']]['articles'][$shoppingcart_article['idArticle']] = [
                    'language_id' => $shoppingcart_article['language']['idLanguage'],
                    'cardmarket_article_id' => $shoppingcart_article['idArticle'],
                    'condition' => Arr::get($shoppingcart_article, 'condition', ''),
                    'unit_price' => $shoppingcart_article['price'],
                    'is_in_shoppingcard' => Arr::get($shoppingcart_article, 'inShoppingCart', false),
                    'is_foil' => Arr::get($shoppingcart_article, 'isFoil', false),
                    'is_reverse_holo' => Arr::get($shoppingcart_article, 'isReverseHolo', false),
                    'is_first_edition' => Arr::get($shoppingcart_article, 'isFirstEd', false),
                    'is_signed' => Arr::get($shoppingcart_article, 'isSigned', false),
                    'is_altered' => Arr::get($shoppingcart_article, 'isAltered', false),
                    'is_playset' => Arr::get($shoppingcart_article, 'isPlayset', false),
                    'cardmarket_comments' => $shoppingcart_article['comments'] ?: null,
                    'amount' => $shoppingcart_article['count'],
                    'has_sync_error' => false,
                    'sync_error' => null,
                    'number_from_cardmarket_comments' => Article::numberFromCardmarketComments($shoppingcart_article['comments']),
                ];
            }

            $this->cardmarket_cards[$shoppingcart_article['idProduct']]['amount'] += $shoppingcart_article['count'];
        }
    }

    private function saveInFile(): void
    {
        if (! is_dir($directory = storage_path('app/imports/cardmarket'))) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(storage_path('app/imports/cardmarket/' . $this->user_id . '-stockfile-' . $this->game_id . '.json'), json_encode($this->cardmarket_cards));
    }
}