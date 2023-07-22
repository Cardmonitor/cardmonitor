<?php

namespace App\Http\Controllers\Cardmarket\Expansions;

use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\Expansions\Expansion;
use App\Support\Users\CardmarketApi;

class ExpansionController extends Controller
{
    protected CardmarketApi $CardmarketApi;

    public function show(Expansion $expansion)
    {
        $cardmarketApi = App::make('CardmarketApi');

        $cardmarket_expansion = $cardmarketApi->expansion->singles($expansion->id);

        return $cardmarket_expansion;
    }
}
