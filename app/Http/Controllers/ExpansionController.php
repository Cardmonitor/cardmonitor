<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

class ExpansionController extends Controller
{
    protected $baseViewPath = 'expansion';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return Expansion::game($request->input('game_id'))
                ->search($request->input('searchtext'))
                ->orderBy('name', 'ASC')
                ->paginate();
        }

        return view($this->baseViewPath . '.index')
            ->with('games', Game::keyValue());
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
        $attributes = $request->validate([
            'abbreviation' => 'required|string|min:3',
            'game_id' => 'required|int'
        ]);

        $cardmarketApi = App::make('CardmarketApi');

        $response = $cardmarketApi->expansion->find(Game::ID_MAGIC);
        $cardmarket_expansions = array_filter($response['expansion'], function ($cardmarket_expansion) use ($attributes) {
            return $cardmarket_expansion['abbreviation'] == strtoupper($attributes['abbreviation']);
        });

        if (count($cardmarket_expansions) !== 1) {
            throw ValidationException::withMessages(['abbreviation' => 'Not available on Cardmarket.']);
        }

        Artisan::queue('expansion:import', [
            'expansion' => $cardmarket_expansions[array_key_first($cardmarket_expansions)]['idExpansion'],
        ]);

        return [
            'status' => 'Import started',
            'expansion_id' => $cardmarket_expansions[array_key_first($cardmarket_expansions)]['idExpansion'],
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Expansions\Expansion  $expansion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Expansion $expansion)
    {
        Artisan::queue('expansion:import', [
            'expansion' => $expansion->id,
        ]);

        return [
            'status' => 'Import started',
            'expansion_id' => $expansion->id,
        ];
    }
}
