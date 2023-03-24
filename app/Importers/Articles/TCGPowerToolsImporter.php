<?php

namespace App\Importers\Articles;

use Generator;
use Carbon\Carbon;
use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use Illuminate\Support\Collection;
use App\Models\Localizations\Language;

class TCGPowerToolsImporter
{
    const SOURCE_SLUG = 'tcg-powertools';

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
        $header = [];
        foreach (self::parseCsv($this->filepath) as $row_index => $row) {
            if ($row_index == 0) {
                $header = self::parseHeader($row);
                continue;
            }
            $this->importArticle($row_index, $header, $row);
        }
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

    public function importArticle(int $row_index, array $header, array $row): void
    {
        $card = Card::firstOrImport((int)$row[$header['cardmarketid']]);
        $source_sort = $this->getSourceSort($header, $row);

        for ($index=1; $index <= $row[$header['quantity']]; $index++) {
            $values = [
                'user_id' => $this->user_id,
                'card_id' => $card->id,
                'language_id' => $this->getLanguageId($row[$header['language']]),
                'cardmarket_article_id' => null,
                'condition' => $row[$header['condition']],
                'unit_price' => $row[$header['price']],
                'unit_cost' => 0,
                'sold_at' => null,
                'is_in_shoppingcard' => false,
                'is_foil' => ($row[$header['isfoil']] == 'true'),
                'is_signed' => false,
                'is_altered' => false,
                'is_playset' => false,
                'cardmarket_comments' => $row[$header['comment']],
                'has_sync_error' => false,
                'sync_error' => null,
                'source_sort' => $source_sort,
            ];
            $attributes = [
                'source_slug' => self::SOURCE_SLUG,
                'source_id' => $row_index,
                'index' => $index,
                'storage_id' => $this->storage->id,
            ];

            $this->articles->push(Article::updateOrCreate($attributes, $values));
        }
    }

    private function getSourceSort(array $header, array $row): int
    {
        if (! Arr::has($row, $header['listedat'])) {
            return 0;
        }

        $date = Arr::get($row, $header['listedat'], '01-01-1970 02:00:00');
        if (empty($date)) {
            return 0;
        }

        return Carbon::createFromFormat('d-m-Y H:i:s', Arr::get($row, $header['listedat'], '01-01-1970 02:00:00'))->timestamp;
    }
}