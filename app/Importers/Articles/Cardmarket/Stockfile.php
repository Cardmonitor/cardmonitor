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
use App\Transformers\Articles\Csvs\Transformer;

class Stockfile
{
    private string $path;
    private int $user_id;
    private int $game_id;
    private array $cardmarket_cards = [];

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

    public function setCardmarketCards(): array
    {
        $expansions = Expansion::where('game_id', $this->game_id)->get()->keyBy('abbreviation');
        $no_storage = Storage::noStorage($this->user_id)->first();
        $no_storage_id = $no_storage->id ?? null;

        $row_count = 0;
        foreach ($this->parse() as $stock_row) {
            if ($row_count === 0) {
                $row_count++;
                continue;
            }

            // Expansion not found, import it
            if (! Arr::has($expansions, $stock_row[4])) {
                $card = Card::import($stock_row[Article::CSV_CARDMARKET_PRODUCT_ID]);
                $expansions = Expansion::where('game_id', $this->game_id)->get()->keyBy('abbreviation');

                Artisan::queue('expansion:import', ['expansion' => $card->expansion_id]);
            }

            $stock_row['expansion_id'] = $expansions[$stock_row[4]]->id;
            $stock_row_id = $stock_row[Article::CSV_CARDMARKET_ARTICLE_ID];
            $stock_row_ids[] = $stock_row_id;

            Card::firstOrImport($stock_row[Article::CSV_CARDMARKET_PRODUCT_ID]);

            $cardmarket_article = Transformer::transform($this->game_id, $stock_row);

            $cardmarket_article['expansion_id'] = $stock_row['expansion_id'];

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

        if (! is_dir($directory = storage_path('app/imports/cardmarket'))) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(storage_path('app/imports/cardmarket/stockfile.json'), json_encode($this->cardmarket_cards));

        return $this->cardmarket_cards;
    }
}