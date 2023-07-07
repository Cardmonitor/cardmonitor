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
        {--create : Create new articles}
        {--update : Update existing articles}';

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
        $states_count = [
            'CREATED' => 0,
            'EXISTED' => 0,
            'NOT_FOUND' => 0,
            'UPDATED' => 0,
        ];
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
            elseif ($this->option('update')) {
                $affected_rows = Article::where('id', $attributes['id'])->update([
                    'cardmarket_article_id' => $attributes['cardmarket_article_id'],
                    'condition_sort' => $attributes['condition_sort'],
                    'condition' => $attributes['condition'],
                    'index' => $attributes['index'],
                    'is_altered' => $attributes['is_altered'],
                    'is_foil' => $attributes['is_foil'],
                    'is_reverse_holo' => $attributes['is_reverse_holo'],
                    'is_first_edition' => $attributes['is_first_edition'],
                    'is_playset' => $attributes['is_playset'],
                    'is_signed' => $attributes['is_signed'],
                    'language_id' => $attributes['language_id'],
                ]);
                $model_state = $affected_rows ? 'UPDATED' : 'NOT_FOUND';
            }
            else {
                $article = Article::firstOrNew([
                    'id' => $attributes['id'],
                ], $attributes);
                $model_state = (!$article->exists ? 'CREATED' : 'EXISTED');
            }

            $states_count[$model_state]++;

            $this->line(now()->format('Y-m-d H:i:s') . "\t" . $row_index . "\t" . $attributes['id'] . "\t" . $model_state);
        }

        foreach ($states_count as $action => $count) {
            $this->line($action . ': ' . $count);
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
