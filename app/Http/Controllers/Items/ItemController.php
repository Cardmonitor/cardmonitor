<?php

namespace App\Http\Controllers\Items;

use App\Http\Controllers\Controller;
use App\Models\Items\Custom;
use App\Models\Items\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    protected $baseViewPath = 'item';

    public function __construct()
    {
        $this->authorizeResource(Item::class, 'item');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return auth()->user()
                ->items()
                ->search($request->input('searchtext'))
                ->orderBy('name', 'ASC')
                ->get();
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
        return Custom::create($request->validate([
            'name' => 'required|string',
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Items\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        return view($this->baseViewPath . '.show')
            ->with('model', $item->load(['quantities']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Items\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function edit(Item $item)
    {
        return view($this->baseViewPath . '.edit')
            ->with('model', $item);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Items\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Item $item)
    {
        $rules = [
            'unit_cost_formatted' => 'required|formated_number',
        ];

        if ($item->isEditable()) {
            $rules['name'] = 'required|string';
        }

        $item->update($request->validate($rules));

        if ($request->wantsJson()) {
            return $item;
        }

        return redirect($item->path);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Items\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Item $item)
    {
        if ($isDeletable = $item->isDeletable()) {
            $item->delete();
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
                'text' => 'Kosten <b>' . $item->name . '</b> gelöscht.',
            ];
        }
        else {
            $status = [
                'type' => 'danger',
                'text' => 'Kosten <b>' . $item->name . '</b> kann nicht gelöscht werden.',
            ];
        }

        return redirect(route($this->baseViewPath . '.index'))
            ->with('status', $status);
    }
}
