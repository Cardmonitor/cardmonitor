<?php

namespace App\Importers\Articles;

use Carbon\Carbon;
use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use Illuminate\Support\Collection;
use App\Models\Localizations\Language;
use App\Support\Csv\Csv;

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
        foreach (Csv::parseCsv($this->filepath) as $row_index => $row) {
            if ($row_index == 0) {
                $header = Csv::parseHeader($row);
                continue;
            }
            $this->importArticle($row_index, Csv::combineHeaderAndRow($header, $row));
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

    public function importArticle(int $row_index, array $data): void
    {
        $card = Card::firstOrImport((int)$data['cardmarketid']);
        $source_sort = $this->getSourceSort($data);

        for ($index=1; $index <= $data['quantity']; $index++) {
            $values = [
                'user_id' => $this->user_id,
                'card_id' => $card->id,
                'language_id' => $this->getLanguageId($data['language']),
                'cardmarket_article_id' => null,
                'condition' => $data['condition'],
                'unit_price' => $data['price'],
                'unit_cost' => 0,
                'is_in_shoppingcard' => false,
                'is_foil' => (Arr::get($data, 'isfoil', '') == 'true'),
                'is_reverse_holo' => (Arr::get($data, 'isreverseholo', '') == 'true'),
                'is_first_edition' => (Arr::get($data, 'isfirsted', '') == 'true'),
                'is_signed' => (Arr::get($data, 'issigned', '') == 'true'),
                'is_altered' => (Arr::get($data, 'isaltered', '') == 'true'),
                'is_playset' => (Arr::get($data, 'isplayset', '') == 'true'),
                'cardmarket_comments' => $data['comment'],
                'has_sync_error' => false,
                'sync_error' => null,
                'source_sort' => $source_sort,
                'is_sellable_since' => now(),
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

    private function getSourceSort(array $data): int
    {
        if (! Arr::has($data, 'listedat')) {
            return 0;
        }

        $date = Arr::get($data, 'listedat', '01-01-1970 02:00:00');
        if (empty($date)) {
            return 0;
        }

        return Carbon::createFromFormat('d-m-Y H:i:s', Arr::get($data, 'listedat', '01-01-1970 02:00:00'))->timestamp;
    }
}