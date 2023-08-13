<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class MqttController extends Controller
{
    protected $baseViewPath = 'mqtt';

    public function index()
    {
        return view($this->baseViewPath . '.index');
    }
}
