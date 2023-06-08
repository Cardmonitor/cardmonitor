<?php

namespace App\Http\Controllers\Orders\Export;

use App\Exporters\Orders\CsvExporter;
use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    protected $basePath;

    public function store(Request $request)
    {
        $userId = $request->user()->id;
        $orders = Order::where('user_id', $userId)
            ->state($request->input('state'))
            ->presale($request->input('presale'))
            ->with([
                'articles.language',
                'articles.card.expansion',
                'buyer',
            ])->get();

        $this->basePath = 'export/' . $userId . '/order';
        $this->makeDirectory($this->basePath);

        return [
            'path' => CsvExporter::all($userId, $orders, $this->basePath . '/orders.csv'),
        ];
    }

    protected function makeDirectory($path)
    {
        Storage::disk('public')->makeDirectory($path);

        return $path;
    }
}
