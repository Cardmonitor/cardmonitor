<?php

namespace App\Exporters\Articles;

use App\Support\Csv\Csv;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Storage;

class CsvExporter
{
    const EXPANSION_ATTRIBUTES = [
        'game_id',
        'id',
        'name',
        'abbreviation',
    ];
    const CARD_ATTRIBUTES = [
        'id',
        'cardmarket_product_id',
        'skryfall_card_id',
        'name',
        'number',
    ];
    const ARTICLE_ATTRIBUTES = [
        'id',
        'number',
        'storing_history_id',
        'cardmarket_article_id',
        'index',
        'local_name',
        'unit_price',
        'unit_cost',
        'language_id',
        'language_name',
        'condition',
        'is_foil',
        'is_altered',
        'is_playset',
        'is_signed',
        'source_slug',
        'source_id',
        'source_sort',
        'storage_id',
    ];

    public static function all(LazyCollection $articles, string $path)
    {
        $header = array_merge(self::EXPANSION_ATTRIBUTES, self::CARD_ATTRIBUTES, self::ARTICLE_ATTRIBUTES);

        $collection = new Collection();
        foreach ($articles as $article) {

            $expansion_values = $article->card->expansion ? $article->card->expansion->only(self::EXPANSION_ATTRIBUTES) : [];
            $card_values = array_values($article->card->only(self::CARD_ATTRIBUTES));

            $item = array_merge($expansion_values, $card_values, array_values($article->only(self::ARTICLE_ATTRIBUTES)));

            $collection->push($item);
        }

        $csv = new Csv();
        $csv->collection($collection)
            ->header($header)
            ->callback( function($item) {
                return $item;
            })->save(Storage::disk('public')->path($path));

        return Storage::disk('public')->url($path);
    }
}