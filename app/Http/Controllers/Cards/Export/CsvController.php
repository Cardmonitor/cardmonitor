<?php

namespace App\Http\Controllers\Cards\Export;

use App\APIs\Skryfall\Expansion as SkryfallExpansion;
use App\Http\Controllers\Controller;
use App\Models\Expansions\Expansion;
use App\Models\Localizations\Language;
use App\Support\Csv\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CsvController extends Controller
{
    const CARD_ATTRIBUTES = [
        'id',
        'local_id',
        'expansion_id',
        'name',
        'local_name',
        'number',
        'rarity',
        'website',
        'reprints_count',
        'image_path',
        'full_image_path',
    ];

    const EXPANSION_ATTRIBUTES = [
        'id',
        'name',
        'local_name',
        'abbreviation',
        'released_at',
    ];

    const SKRYFALL_ATTRIBUTES = [
        'id',
        'object',
        'name',
        'layout',
        'image_uri_large',
        'image_uri_png',
        'mana_cost',
        'cmc',
        'type_line',
        'oracle_text',
        'power',
        'toughness',
        'colors_string',
        'color_identity_string',
        'legalities_future',
        'legalities_historic',
        'legalities_standard',
        'legalities_pioneer',
        'legalities_modern',
        'legalities_legacy',
        'legalities_pauper',
        'legalities_vintage',
        'legalities_penny',
        'legalities_commander',
        'legalities_brawl',
        'legalities_duell',
        'legalities_oldschool',
        'set_type',
        'collector_number',
        'rarity',
        'flavor_text',
        'artist',
        'border_color',
        'frame',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $languages = Language::all()->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name']];
        });

        return view('card.export.index')
            ->with('expansions', Expansion::all())
            ->with('skryfall_expansions', SkryfallExpansion::all())
            ->with('languages', $languages);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $language = Language::find($request->input('language_id'));

        $expansion = Expansion::with([
            'cards',
        ])->find($request->input('expansion_id'));
        $expansion->language = $language;
        $skryfall_expansion_code = ($request->input('skryfall_expansion_code') ?: $expansion->abbreviation);

        $skryfallExpansion = SkryfallExpansion::findByCode($skryfall_expansion_code);
        if (isset($skryfallExpansion)) {
            $skryfallExpansion->cards = $skryfallExpansion->cards;
        }

        $this->basePath = 'export/cards';
        Storage::disk('public')->makeDirectory($this->basePath);

        return [
            'files' => [
                $this->cardmarketCsv($expansion, $language, $skryfallExpansion),
                $this->skryfallCsv($skryfallExpansion),
            ],
        ];
    }

    protected function cardmarketCsv(Expansion $expansion, Language $language, $skryfallExpansion)
    {
        $hasSkryfallExpansion = (is_null($skryfallExpansion) ? false : true);

        $basename = 'cardmarket-' . strtolower($expansion->abbreviation) . '-' . $language->code . '.csv';
        $path = $this->basePath . '/' . $basename;

        $collection = new Collection();
        $header = array_merge(self::EXPANSION_ATTRIBUTES, self::CARD_ATTRIBUTES, self::SKRYFALL_ATTRIBUTES);
        $expansion_values = array_values($expansion->only(self::EXPANSION_ATTRIBUTES));
        foreach ($expansion->cards as $key => &$card) {
            $card->language = $expansion->language;
            $card_values = array_values($card->only(self::CARD_ATTRIBUTES));
            $skryfall_values = $hasSkryfallExpansion ? array_values($this->getSkyfallValues($skryfallExpansion, $card->number ?? '')) : [];

            $item = array_merge($expansion_values, $card_values, $skryfall_values);
            $collection->push($item);
        }

        $csv = new Csv();
        $csv->collection($collection)
            ->header($header)
            ->callback( function($item) {
                return $item;
            })->save(Storage::disk('public')->path($path));

        return [
            'basename' => $basename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    protected function getSkyfallValues(SkryfallExpansion $expansion, string $collector_number) : array
    {
        $card = $expansion->cards->firstByNumber($collector_number);
        if (is_null($card)) {
            return [];
        }

        return $card->only(self::SKRYFALL_ATTRIBUTES);
    }

    protected function skryfallCsv($skryfallExpansion)
    {
        if (is_null($skryfallExpansion)) {
            return null;
        }

        $basename = 'skryfall-' . strtolower($skryfallExpansion->code) . '.csv';
        $path = $this->basePath . '/' . $basename;

        $collection = new Collection();
        $header = self::SKRYFALL_ATTRIBUTES;
        foreach ($skryfallExpansion->cards as $key => $card) {
            $collection->push($card->only(self::SKRYFALL_ATTRIBUTES));
        }

        $csv = new Csv();
        $csv->collection($collection)
            ->header($header)
            ->callback( function($item) {
                return $item;
            })->save(Storage::disk('public')->path($path));

        return [
            'basename' => $basename,
            'url' => Storage::disk('public')->url($path),
        ];
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
