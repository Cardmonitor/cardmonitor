<?php

namespace App\Console\Commands\Article\Price;

use App\Models\Games\Game;
use App\User;
use Generator;
use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:price:update
        {user}
        {--update : Update existing prices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update prices for all articles from cardmarket API';

    protected User $user;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->user = User::findOrFail($this->argument('user'));

        $states_count = [
            'FOUND' => 0,
            'NOT_FOUND' => 0,
        ];

        $filename = $this->user->cardmarketApi->downloadStockFile($this->user->id, Game::ID_MAGIC);
        if ($filename) {
            $this->line('Downloaded stock file: ' . $filename);
        }
        else {
            $this->error('Could not download stock file');
            return self::FAILURE;
        }

        foreach ($this->parseCsv(storage_path('app/' . $filename)) as $row_index => $row) {
            if ($row_index === 0) {
                continue;
            }

            $cardmarket_article_id = $row[0];
            $unit_price = $row[6];
            $amount = $row[14];

            if ($this->option('update')) {
                $article_count = $this->user->articles()->where('cardmarket_article_id', $cardmarket_article_id)->update([
                    'unit_price' => $unit_price,
                ]);
            }
            else {
                $article_count = $this->user->articles()->where('cardmarket_article_id', $cardmarket_article_id)->count();
            }

            $states_key = $article_count === $amount ? 'FOUND' : 'NOT_FOUND';
            $states_count[$states_key]++;

            $this->line(now()->format('Y-m-d H:i:s') . "\t" . $cardmarket_article_id . "\t" . $unit_price . '€' . "\t" . $article_count . '/' . $amount . "\t" . $states_key);
        }

        foreach ($states_count as $action => $count) {
            $this->line($action . ': ' . $count);
        }

        return self::SUCCESS;
    }

    private function parseCsv(string $filename): Generator
    {
        $handle = fopen($filename, 'r');
        if ($handle) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                yield $data;
            }
            fclose($handle);
        }
    }
}
