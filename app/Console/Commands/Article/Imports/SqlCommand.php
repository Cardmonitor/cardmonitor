<?php

namespace App\Console\Commands\Article\Imports;

use App\User;
use Generator;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use App\Models\Articles\Article;

class SqlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:imports:sql
        {path}
        {--create : Create new articles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Articles from SQL CSV';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $header = [];
        $filepath = storage_path('app/' . $this->argument('path'));

        foreach (self::parseCsv($filepath) as $row_index => $row) {
            if ($row_index === 0) {
                $header = $row;
                continue;
            }

            // String NULL to NULL
            foreach ($row as $column_index => $column) {
                if ($column === 'NULL') {
                    $row[$column_index] = null;
                }
            }

            $attributes = array_combine($header, $row);
            Arr::forget($attributes, ['created_at', 'updated_at']);

            if ($this->option('create')) {
                $article = Article::firstOrCreate([
                    'id' => $attributes['id'],
                ], $attributes);
                $model_state = ($article->wasRecentlyCreated ? 'CREATED' : 'EXISTED');
            }
            else {
                $article = Article::firstOrNew([
                    'id' => $attributes['id'],
                ], $attributes);
                $model_state = (!$article->exists ? 'CREATED' : 'EXISTED');
            }
            $article = Article::firstOrNew([
                'id' => $attributes['id'],
            ], $attributes);

            echo now()->format('Y-m-d H:i:s') . "\t" . $row_index . "\t" . $article->id . "\t" . $model_state . PHP_EOL;
        }

        return self::SUCCESS;
    }

    public static function parseCsv(string $filepath): Generator
    {
        $handle = fopen($filepath, "r");
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            yield str_getcsv($raw_string, ';');
        }
        fclose($handle);
    }
}
