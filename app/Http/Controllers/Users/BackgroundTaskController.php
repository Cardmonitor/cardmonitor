<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use App\Support\BackgroundTasks;
use App\Http\Controllers\Controller;

class BackgroundTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BackgroundTasks $BackgroundTasks)
    {
        return $BackgroundTasks->all();
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
            'background_tasks' => 'required|array',
        ]);

        foreach ($attributes['background_tasks'] as $task) {
            $BackgroundTasks->put($task, 1);
        }

        return $BackgroundTasks->all();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(BackgroundTasks $BackgroundTasks, string $task)
    {
        $BackgroundTasks->forget($task);

        return $BackgroundTasks->all();
    }
}
