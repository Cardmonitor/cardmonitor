<?php

namespace App\Http\Controllers\Articles\Stock;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Console\Commands\Article\Imports\Cardmarket\StockfileCommand;

class LogFileController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $filepath = StockfileCommand::zipArchivePath($user);

        if (!file_exists($filepath)) {
            return redirect()->route('article.index')->with('status', [
                'type' => 'danger',
                'text' => 'Die Log-Datei existiert nicht.',
            ]);
        }

        return response()->download($filepath);
    }

}