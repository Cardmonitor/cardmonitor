<?php

namespace App\Http\Controllers\Storages;

use App\Models\Games\Game;
use Illuminate\Http\Request;
use App\Models\Storages\Storage;
use App\Http\Controllers\Controller;
use App\Models\Expansions\Expansion;

class StorageController extends Controller
{
    protected $baseViewPath = 'storage';

    public function __construct()
    {
        $this->authorizeResource(Storage::class, 'storage');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $storages = auth()->user()
                ->storages()
                ->withCount(['contents'])
                ->withDepth()
                ->defaultOrder()
                ->get();

            foreach ($storages as $key => $storage) {
                $storage->articleStats = $storage->articleStats;
                $storage->append('isDeletable');
            }

            return $storages;
        }

        return view($this->baseViewPath . '.index');
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
        return Storage::create($request->validate([
            'name' => 'required|string',
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Storages\Storage  $storage
     * @return \Illuminate\Http\Response
     */
    public function show(Storage $storage)
    {
        $storage->load([
            'articles' => function ($query) {
                $query->with([
                    'card',
                    'language',
                ])->orderBy('slot', 'ASC');
            }
        ]);

        $storage->load([
            'contents',
            'descendants',
            'parent',
        ]);

        foreach ($storage->descendants as $key => &$descendant) {
                $descendant->articleStats = $descendant->articleStats;
            }

        return view($this->baseViewPath . '.show')
            ->with('model', $storage)
            ->with('games', Game::keyValue())
            ->with('expansions', Expansion::game(Game::ID_MAGIC)->orderBy('name', 'ASC')->get());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Storages\Storage  $storage
     * @return \Illuminate\Http\Response
     */
    public function edit(Storage $storage)
    {
        return view($this->baseViewPath . '.edit')
            ->with('model', $storage)
            ->with('storages', auth()->user()->storages()
                ->withDepth()
                ->defaultOrder()
                ->get());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Storages\Storage  $storage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Storage $storage)
    {
        $attributes = $request->validate([
            'name' => 'required|string',
            'slots' => 'required|integer',
            'parent_id' => 'nullable|exists:storages,id',
        ]);

        if (is_null($attributes['parent_id'])) {
            $storage->makeRoot();
        }
        else {
            $storage->appendToNode(Storage::find($attributes['parent_id']));
        }

        unset($attributes['parent_id']);

        $storage->update($attributes);

        if ($request->wantsJson()) {
            return $storage;
        }

        return redirect($storage->path);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Storages\Storage  $storage
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Storage $storage)
    {
        if ($isDeletable = $storage->isDeletable()) {
            $storage->delete();
        }

        if ($request->wantsJson())
        {
            return [
                'deleted' => $isDeletable,
            ];
        }

        if ($isDeletable) {
            $status = [
                'type' => 'success',
                'text' => 'Lagerplatz <b>' . $storage->name . '</b> gelöscht.',
            ];
        }
        else {
            $status = [
                'type' => 'danger',
                'text' => 'Lagerplatz <b>' . $storage->name . '</b> kann nicht gelöscht werden.',
            ];
        }

        return redirect(route('storages.index'))
            ->with('status', $status);
    }
}
