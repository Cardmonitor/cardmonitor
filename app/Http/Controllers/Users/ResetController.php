<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Support\BackgroundTasks;
use App\User;
use Illuminate\Http\Request;

class ResetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BackgroundTasks $BackgroundTasks)
    {
        auth()->user()->reset();

        $BackgroundTasks->flush();

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Hintergrundtasks zurückgesetzt.',
        ]);
    }
}
