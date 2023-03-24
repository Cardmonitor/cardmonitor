<?php

namespace App\Http\Controllers\Articles\Imports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            Log::error($th, [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
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
            'text' => 'Artikel wurden importiert.',
        ]);
    }
}
