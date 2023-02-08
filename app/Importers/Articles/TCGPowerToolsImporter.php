<?php

namespace App\Importers\Articles;

use App\Models\Cards\Card;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Models\Localizations\Language;
use Generator;
use Illuminate\Support\Collection;

class TCGPowerToolsImporter
{
    const SOURCE_SLUG = 'tcg-powertools';

    const COLUMN_CARDMARKET_PRODUCT_ID = 0;
    const COLUMN_QUANTITY = 1;
    const COLUMN_CONDITION = 6;
    const COLUMN_LANGUAGE = 7;
    const COLUMN_IS_FOIL = 8;
    const COLUMN_PRICE = 11;
    const COLUMN_COMMENT = 12;

    public Collection $articles;
    private array $languages = [];
    private int $user_id;

    private string $filepath = '';

    private Storage $storage;

    public static function import(int $user_id, string $filepath): self
    {
        $importer = new self($user_id, $filepath);
        $importer->importFile();

        return $importer;
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

    public function __construct(int $user_id, string $filepath)
    {
        $this->user_id = $user_id;
        $this->filepath = $filepath;

        $this->setLanguages();
    }

    public function importFile(): void
    {
        $this->setStorage();
        $this->articles = collect();
        foreach (self::parseCsv($this->filepath) as $row_index => $row) {
            if ($row_index == 0) {
                continue;
            }
            $this->importArticle($row_index, $row);
        }

        $this->articles->sortBy('local_name')->each(function($article, $key) {
            $article->update([
                'source_sort' => $key,
            ]);
        });

    }

    private function setLanguages(): void
    {
        $this->languages = Language::all()->keyBy('name')->toArray();
    }

    private function getLanguageId(string $name): int
    {
        return $this->languages[$name]['id'];
    }

    private function setStorage(): void
    {
        $parent_storage = Storage::firstOrCreate([
            'user_id' => $this->user_id,
            'name' => 'TCG Power Tools',
        ]);

        $this->storage = Storage::firstOrCreate([
            'user_id' => $this->user_id,
            'name' => basename($this->filepath),
            'parent_id' => $parent_storage->id,
        ]);
    }

    public function importArticle(int $row_index, array $row): void
    {
        $card = Card::firstOrImport((int)$row[self::COLUMN_CARDMARKET_PRODUCT_ID]);

        for ($index=1; $index <= $row[self::COLUMN_QUANTITY]; $index++) {
            $values = [
                'user_id' => $this->user_id,
                'card_id' => $card->id,
                'language_id' => $this->getLanguageId($row[self::COLUMN_LANGUAGE]),
                'cardmarket_article_id' => null,
                'condition' => $row[self::COLUMN_CONDITION],
                'unit_price' => $row[self::COLUMN_PRICE],
                'unit_cost' => 0,
                'sold_at' => null,
                'is_in_shoppingcard' => false,
                'is_foil' => ($row[self::COLUMN_IS_FOIL] == 'true'),
                'is_signed' => false,
                'is_altered' => false,
                'is_playset' => false,
                'cardmarket_comments' => $row[self::COLUMN_COMMENT],
                'has_sync_error' => false,
                'sync_error' => null,
                'storage_id' => $this->storage->id,
            ];
            $attributes = [
                'source_slug' => self::SOURCE_SLUG,
                'source_id' => $row_index,
                'index' => $index,
            ];

            $this->articles->push(Article::updateOrCreate($attributes, $values));
        }
    }
}