<?php

namespace App\Http\Controllers\Apis;

use App\Models\Apis\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function update(Request $request, Api $api)
    {
        $attributes = $request->validate([
            'app_token' => 'required|string',
            'app_secret' => 'required|string',
            'access_token' => 'required|string',
            'access_token_secret' => 'required|string',
        ]);

        $request->user()->api->update($attributes);

        return back()->with('status', [
            'type' => 'success',
            'text' => 'API-Daten wurden erfolgreich gespeichert.'
        ]);
    }

    public function destroy(Request $request, Api $api)
    {
        $request->user()->api->reset();

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Verbindung wurde getrennt.'
        ]);
    }
}
