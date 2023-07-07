<?php

namespace App\Importers\Articles;

use Generator;
use App\Models\Cards\Card;
use Illuminate\Support\Str;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use Illuminate\Support\Arr;

class MagicSorterImporter
{
    const SOURCE_SLUG = 'magic-sorter';

    public array $articles_by_position = [];
    private int $user_id;

    private string $filepath = '';

    private Storage $parent_storage;
    private array $storages = [];

    private string $condition = 'NM';
    private int $language_id = 1;
    private bool $is_foil = false;

    public static function import(int $user_id, string $filepath, string $condition, int $language_id, bool $is_foil): self
    {
        $importer = new self($user_id, $filepath, $condition, $language_id, $is_foil);
        $importer->importFile();

        return $importer;
    }

    public static function parseHeader(array $row): array
    {
        $header = [];
        foreach ($row as $column_index => $column) {
            $header[Str::slug($column)] = $column_index;
        }

        return $header;
    }

    public static function parseCsv(string $filepath): Generator
    {
        $handle = fopen($filepath, "r");
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            yield str_getcsv($raw_string, ',');
        }
        fclose($handle);
    }

    public function __construct(int $user_id, string $filepath, string $condition, int $language_id, bool $is_foil)
    {
        $this->user_id = $user_id;
        $this->filepath = $filepath;
        $this->condition = $condition;
        $this->language_id = $language_id;
        $this->is_foil = $is_foil;
    }

    public function importFile(): void
    {
        $this->setParentStorage();
        $header = [];
        foreach (self::parseCsv($this->filepath) as $row_index => $row) {
            if ($row_index == 0) {
                $header = self::parseHeader($row);
                continue;
            }

            // Skip rows without ecommerce_id -> Card not found
            if (empty($row[$header['ecommerce-id']])) {
                continue;
            }

            $this->importArticle($row_index, $header, $row);
        }

        $this->sortByHeightReverse();
    }

    private function setParentStorage(): void
    {
        $parent_storage = Storage::firstOrCreate([
            'user_id' => $this->user_id,
            'name' => 'Magic Sorter',
        ]);

        $this->parent_storage = Storage::firstOrCreate([
            'user_id' => $this->user_id,
            'name' => basename($this->filepath),
            'parent_id' => $parent_storage->id,
        ]);
    }

    public function importArticle(int $row_index, array $header, array $row): void
    {
        $card = Card::firstOrImport((int)$row[$header['ecommerce-id']]);
        $position = $row[$header['position']];

        $values = [
            'user_id' => $this->user_id,
            'card_id' => $card->id,
            'language_id' => $this->language_id,
            'cardmarket_article_id' => null,
            'condition' => $this->condition,
            'unit_price' => $this->getPrice($row[$header['price']]),
            'unit_cost' => 0,
            'sold_at' => null,
            'is_in_shoppingcard' => false,
            'is_foil' => $this->is_foil,
            'is_signed' => false,
            'is_altered' => false,
            'is_playset' => false,
            'cardmarket_comments' => null,
            'has_sync_error' => false,
            'sync_error' => null,
            'source_sort' => $row[$header['height']],
        ];
        $attributes = [
            'source_slug' => self::SOURCE_SLUG,
            'source_id' => $row_index,
            'storage_id' => $this->getStorage($position)->id,
        ];

        $this->articles_by_position[$position][] = Article::updateOrCreate($attributes, $values);
    }

    private function getPrice(string $price): float
    {
        return max(0.5, str_replace(',', '.', $price) * 5);
    }

    private function getStorage(int $position): Storage
    {
        if (Arr::has($this->storages, $position)) {
            return $this->storages[$position];
        }

        return $this->storages[$position] = Storage::firstOrCreate([
            'user_id' => $this->user_id,
            'name' => $position,
            'parent_id' => $this->parent_storage->id,
        ]);
    }

    /**
     * Sorts the articles by height in reverse order.
     * This is needed because the the first card on top of the heap would be the last in cardmonitor.
     */
    private function sortByHeightReverse(): void
    {
        foreach ($this->articles_by_position as $articles) {
            $this->reverseSortArticles($articles);
        }
    }

    private function reverseSortArticles(array $articles): void
    {
        $source_sort = 0;
        $articles = collect($articles)->sortByDesc('source_sort');
        foreach ($articles as $article) {
            $article->update([
                'source_sort' => $source_sort,
            ]);
            $source_sort++;
        }
    }
}