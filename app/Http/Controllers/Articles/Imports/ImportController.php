<?php

namespace App\Http\Controllers\Articles\Imports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Importers\Articles\TCGPowerToolsImporter;

class ImportController extends Controller
{
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'file' => 'required|file',
            'game_id' => 'required|integer',
            'type' => 'required|in:tcg-powertools',
        ]);

        $user = auth()->user();

        $filename = $attributes['file']->storeAs('', $attributes['file']->getClientOriginalName());

        try {
            TCGPowerToolsImporter::import($user->id, Storage::path($filename));
        } catch (\Throwable $th) {
            return back()->with('status', [
                'type' => 'danger',
                'text' => 'Datei konnte nicht importiert werden.',
            ]);
        }
        finally {
            Storage::delete($filename);
        }

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Arkitel wurden importiert.',
        ]);
    }
}
