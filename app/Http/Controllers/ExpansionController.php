<?php

namespace App\Http\Controllers;

use App\Models\Games\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Support\BackgroundTasks;
use Illuminate\Support\Facades\DB;
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
            return Expansion::query()->game($request->input('game_id'))
                ->search($request->input('searchtext'))
                ->orderBy('name', 'ASC')
                ->paginate();
        }

        return view($this->baseViewPath . '.index')
            ->with('games', Game::keyValue())
            ->with('background_tasks', BackgroundTasks::make()->all());
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
    public function store(Request $request, BackgroundTasks $BackgroundTasks)
    {
        $attributes = $request->validate([
            'abbreviation' => 'required|string|min:3',
            'game_id' => 'required|int'
        ]);

        $cardmarketApi = App::make('CardmarketApi');

        $response = $cardmarketApi->expansion->find($attributes['game_id']);
        $cardmarket_expansions = array_filter($response['expansion'], function ($cardmarket_expansion) use ($attributes) {
            return $cardmarket_expansion['abbreviation'] == strtoupper($attributes['abbreviation']);
        });

        if (count($cardmarket_expansions) !== 1) {
            throw ValidationException::withMessages(['abbreviation' => 'Not available on Cardmarket.']);
        }

        $expansion_id = $cardmarket_expansions[array_key_first($cardmarket_expansions)]['idExpansion'];
        $BackgroundTasks->put('expansion:import.' . $expansion_id, 1);

        Artisan::queue('expansion:import', [
            'expansion' => $expansion_id,
            'user' => auth()->user()->id,
        ]);

        return response()->json([
            'status' => 'Der Import der Erweiterung wurde gestartet.',
            'expansion_id' => $expansion_id,
            'background_tasks' => $BackgroundTasks->all(),
        ], Response::HTTP_CREATED);
    }

    public function show(Expansion $expansion)
    {
        $expansion->loadCount('cards');

        $expansion->load([
            'game',
        ]);

        $expansion->load([
            'cards' => function ($query) {
                return $query->orderBy(DB::raw('CAST(number AS SIGNED INTEGER)'), 'ASC');
            },
        ]);

        return view($this->baseViewPath . '.show')
            ->with('model', $expansion);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Expansions\Expansion  $expansion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BackgroundTasks $BackgroundTasks, Expansion $expansion)
    {
        $backgroundtask_key = 'expansion.import.' . $expansion->id;
        $BackgroundTasks->put($backgroundtask_key, 1);

        Artisan::queue('expansion:import', [
            'expansion' => $expansion->id,
            'user' => auth()->user()->id,
        ]);

        if ($request->wantsJson()) {
            return [
                'status' => 'Das Update der Erweiterung wurde gestartet.',
                'expansion_id' => $expansion->id,
                'background_tasks' => $BackgroundTasks->all(),
                'backgroundtask_key' => $backgroundtask_key,
            ];
        }

        return redirect($expansion->path)
            ->with('status', [
                'type' => 'success',
                'text' => 'Der Import der Erweiterung wurde gestartet.',
            ]);

    }
}
