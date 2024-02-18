<?php

namespace App\Http\Controllers\Articles\Imports;

use App\Enums\Articles\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Importers\Articles\MagicSorterImporter;
use App\Importers\Articles\TCGPowerToolsImporter;

class ImportController extends Controller
{
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'file' => 'required|file',
            'game_id' => 'required|integer',
            'condition' => 'required_if:type,' . Source::MAGIC_SORTER->value . '|string',
            'language_id' => 'required_if:type,' . Source::MAGIC_SORTER->value . '|integer',
            'is_foil' => 'required_if:type,' . Source::MAGIC_SORTER->value . '|boolean',
            'type' => 'required|in:' . Source::TCG_POWERTOOLS->value, Source::MAGIC_SORTER->value,
        ]);

        $user = auth()->user();

        $filename = $attributes['file']->storeAs('', $attributes['file']->getClientOriginalName());

        try {
            match ($attributes['type']) {
                Source::TCG_POWERTOOLS->value => TCGPowerToolsImporter::import($user->id, Storage::path($filename)),
                Source::MAGIC_SORTER->value => MagicSorterImporter::import($user->id, Storage::path($filename), $attributes['condition'], $attributes['language_id'], $attributes['is_foil']),
            };
        } catch (\Throwable $th) {
            report($th);
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
