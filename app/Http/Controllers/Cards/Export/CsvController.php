<?php

namespace App\Http\Controllers\Cards\Export;

use App\APIs\Skryfall\Expansion as SkryfallExpansion;
use App\Http\Controllers\Controller;
use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Localizations\Language;
use App\Support\Csv\Csv;
use Illuminate\Http\File;
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
    ];

    const EXPANSION_ATTRIBUTES = [
        'id',
        'name',
        'local_name',
        'abbreviation',
        'released_at',
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

        $this->basePath = 'export/cards';
        Storage::disk('public')->makeDirectory($this->basePath);

        return [
            'files' => [
                $this->cardmarketCsv($expansion, $language),
            ],
        ];
    }

    protected function cardmarketCsv(Expansion $expansion, Language $language)
    {
        $basename = 'cardmarket-' . strtolower($expansion->abbreviation) . '-' . $language->code . '.csv';
        $path = $this->basePath . '/' . $basename;

        $collection = new Collection();
        $header = array_merge(self::EXPANSION_ATTRIBUTES, self::CARD_ATTRIBUTES);
        $expansion_values = array_values($expansion->only(self::EXPANSION_ATTRIBUTES));
        foreach ($expansion->cards as $key => &$card) {
            $card->language = $expansion->language;
            $card_values = array_values($card->only(self::CARD_ATTRIBUTES));

            $item = array_merge($expansion_values, $card_values);
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
