<?php

namespace App\Console\Commands\Article\Imports\Cardmarket;

use App\User;
use App\Models\Games\Game;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use App\Models\Articles\Article;

class StockfileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:imports:cardmarket:stockfile {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Articles from Cardmarket Stockfile';

    protected User $user;

    private $csv_file_handle;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->user = User::find($this->argument('user'));

        $csv_path = storage_path('app/public/' . $this->user->id . '-stock-' . Game::ID_MAGIC . '-log.csv');
        if (file_exists($csv_path)) {
            unlink($csv_path);
        }
        $this->csv_file_handle = fopen($csv_path, 'w');
        $header = array_keys($this->output(0, '', new Article(), []));
        fputcsv($this->csv_file_handle, $header, ';');

        $path = storage_path('app/' . $this->user->id . '-stock-' . Game::ID_MAGIC . '.csv');
        $Stockfile = new \App\Importers\Articles\Cardmarket\Stockfile($this->user->id, $path, Game::ID_MAGIC);
        $Stockfile->download();
        $cardmarket_cards = $Stockfile->setCardmarketCards();
        $stockfile_article_count = 0;
        $all_updated_article_ids = [];

        $import_states = [
            'NUMBER' => 0,
            'CARDMARKET_ID' => 0,
            'SIMILAR' => 0,
            'CARD' => 0,
            'DELETED' => 0,
            'CREATED' => 0,
            'DELETED_REST' => 0,
        ];

        foreach ($cardmarket_cards as $cardmarket_product_id => &$cardmarket_card) {

            $stockfile_article_count += $cardmarket_card['amount'];

            $articles_for_card = Article::where('user_id', $this->user->id)
                ->where('card_id', $cardmarket_product_id)
                ->whereNull('sold_at')
                ->get()
                ->keyBy('id');

            $cardmarket_articles = $cardmarket_card['articles'];

            $import_state = 'NUMBER';
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {

                if (empty($cardmarket_article['number_from_cardmarket_comments'])) {
                    continue;
                }

                $articles = $articles_for_card
                    ->where('number', $cardmarket_article['number_from_cardmarket_comments'])
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $output = $this->output($cardmarket_product_id, $import_state, $article, $cardmarket_article);
                    $this->line(implode("\t", $output));
                    $this->addToCsvFile($output);
                    $articles_for_card->forget($article->id);
                    $all_updated_article_ids[] = $article->id;
                    $cardmarket_article['amount']--;
                    $import_states[$import_state]++;
                }

                if ($cardmarket_article['amount'] === 0) {
                    unset($cardmarket_articles[$cardmarket_article_id]);
                    break;
                }
            }

            $import_state = 'CARDMARKET_ID';
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                $articles = $articles_for_card
                    ->where('cardmarket_article_id', $cardmarket_article['cardmarket_article_id'])
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $output = $this->output($cardmarket_product_id, $import_state, $article, $cardmarket_article);
                    $this->line(implode("\t", $output));
                    $this->addToCsvFile($output);
                    $articles_for_card->forget($article->id);
                    $all_updated_article_ids[] = $article->id;
                    $cardmarket_article['amount']--;
                    $import_states[$import_state]++;
                }

                if ($cardmarket_article['amount'] === 0) {
                    unset($cardmarket_articles[$cardmarket_article_id]);
                    break;
                }
            }

            $import_state = 'SIMILAR';
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                $articles = $articles_for_card
                    ->where('language_id', $cardmarket_article['language_id'])
                    ->where('condition', Arr::get($cardmarket_article, 'condition', ''))
                    ->where('is_foil', Arr::get($cardmarket_article, 'is_foil', false))
                    ->where('is_signed', Arr::get($cardmarket_article, 'is_signed', false))
                    ->where('is_altered', Arr::get($cardmarket_article, 'is_altered', false))
                    ->where('is_playset', Arr::get($cardmarket_article, 'is_playset', false))
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $output = $this->output($cardmarket_product_id, $import_state, $article, $cardmarket_article);
                    $this->line(implode("\t", $output));
                    $this->addToCsvFile($output);
                    $articles_for_card->forget($article->id);
                    $all_updated_article_ids[] = $article->id;
                    $cardmarket_article['amount']--;
                    $import_states[$import_state]++;
                }

                if ($cardmarket_article['amount'] === 0) {
                    unset($cardmarket_articles[$cardmarket_article_id]);
                    break;
                }
            }

            $import_state = 'CARD';
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                $articles = $articles_for_card
                    ->take($cardmarket_article['amount']);
                foreach ($articles as $article) {
                    $output = $this->output($cardmarket_product_id, $import_state, $article, $cardmarket_article);
                    $this->line(implode("\t", $output));
                    $this->addToCsvFile($output);
                    $articles_for_card->forget($article->id);
                    $all_updated_article_ids[] = $article->id;
                    $cardmarket_article['amount']--;
                    $import_states[$import_state]++;
                }

                if ($cardmarket_article['amount'] === 0) {
                    unset($cardmarket_articles[$cardmarket_article_id]);
                    break;
                }
            }


            $import_state = 'CREATED';
            foreach ($cardmarket_articles as $cardmarket_article_id => &$cardmarket_article) {
                if ($cardmarket_article['amount'] < 1) {
                    continue;
                }
                foreach (range(0, ($cardmarket_article['amount'] - 1)) as $index) {
                    $output = $this->output($cardmarket_product_id, $import_state, new Article(), $cardmarket_article);
                    $this->line(implode("\t", $output));
                    $this->addToCsvFile($output);
                    $import_states[$import_state]++;
                }

            }

            $import_state = 'DELETED';
            $articles = $articles_for_card;
            foreach ($articles as $article) {
                $output = $this->output($cardmarket_product_id, $import_state, $article, [
                    'cardmarket_article_id' => $article->cardmarket_article_id
                ]);
                $this->line(implode("\t", $output));
                $this->addToCsvFile($output);
                $articles_for_card->forget($article->id);
                $all_updated_article_ids[] = $article->id;
                $import_states[$import_state]++;
            }
        }

        $import_state = 'DELETED_REST';
        $articles = Article::where('user_id', $this->user->id)
            ->join('cards', 'cards.id', '=', 'articles.card_id')
            ->where('cards.game_id', Game::ID_MAGIC)
            ->whereNull('articles.sold_at')
            ->whereNotNull('articles.cardmarket_article_id')
            ->whereNotIn('articles.id', $all_updated_article_ids)
            ->cursor();
        foreach ($articles as $article) {
            $output = $this->output($article->card_id, $import_state, $article, [
                'cardmarket_article_id' => $article->cardmarket_article_id
            ]);
            $this->line(implode("\t", $output));
            $this->addToCsvFile($output);
            $articles_for_card->forget($article->id);
            $import_states[$import_state]++;
        }

        foreach ($import_states as $import_state => $count) {
            $this->line($import_state . ': ' . $count);
        }

        $article_count = Article::where('user_id', $this->user->id)->whereNull('sold_at')->whereNotNull('cardmarket_article_id')->count();
        $article_count_without_cardmarket_article_id = Article::where('user_id', $this->user->id)->whereNull('sold_at')->whereNull('cardmarket_article_id')->count();
        $article_count_calculated = $article_count - $article_count_without_cardmarket_article_id - $import_states['DELETED'] - $import_states['DELETED_REST'] + $import_states['CREATED'];

        $this->line('Stockfile Article Count: ' . $stockfile_article_count);
        $this->line('Database Article Count: ' . $article_count);
        $this->line('Database Article Count without Cardmarket Article ID: ' . $article_count_without_cardmarket_article_id);

        $this->line('Database Article Count calculated: ' . $article_count_calculated);
        $this->line('Difference: ' . ($stockfile_article_count - $article_count_calculated));

        fputcsv($this->csv_file_handle, ['Stockfile Article Count', $stockfile_article_count], ';');
        fputcsv($this->csv_file_handle, ['Database Article Count', $article_count], ';');
        fputcsv($this->csv_file_handle, ['Database Article Count without Cardmarket Article ID', $article_count_without_cardmarket_article_id], ';');
        fputcsv($this->csv_file_handle, ['Database Article Count calculated', $article_count_calculated], ';');
        fputcsv($this->csv_file_handle, ['Difference', ($stockfile_article_count - $article_count_calculated)], ';');
    }

    private function output(int $cardmarket_product_id, $import_state, Article $article, array $cardmarket_article = []): array
    {
        return [
            'cardmarket_product_id' => $cardmarket_product_id,
            'cardmarket_article_id' => str_pad($cardmarket_article['cardmarket_article_id'] ?? '-', 10, ' ', STR_PAD_RIGHT),
            'article_id' => $article->id ?? '-',
            'import_state' => str_pad($import_state, 10, ' ', STR_PAD_RIGHT),
            'article_language_id' => $article->language_id ?? '-',
            'cardmarket_language_id' => Arr::get($cardmarket_article, 'language_id', '-'),
            'article_condition' => $article->condition ?? '-',
            'cardmarket_condition' => Arr::has($cardmarket_article, 'condition') ? (int) Arr::get($cardmarket_article, 'condition') : '-',
            'article_is_foil' => is_null($article->is_foil) ? '-' : (int) $article->is_foil,
            'cardmarket_is_foil' => Arr::has($cardmarket_article, 'is_foil') ? (int) Arr::get($cardmarket_article, 'is_foil') : '-',
            'article_is_signed' => is_null($article->is_signed) ? '-' : (int) $article->is_signed,
            'cardmarket_is_signed' => Arr::has($cardmarket_article, 'is_signed') ? (int) Arr::get($cardmarket_article, 'is_signed') : '-',
            'article_is_altered' => is_null($article->is_altered) ? '-' : (int) $article->is_altered,
            'cardmarket_is_altered' => Arr::has($cardmarket_article, 'is_altered') ? (int) Arr::get($cardmarket_article, 'is_altered') : '-',
            'article_is_playset' => is_null($article->is_playset) ? '-' : (int) $article->is_playset,
            'cardmarket_is_playset' => Arr::has($cardmarket_article, 'is_playset') ? (int) Arr::get($cardmarket_article, 'is_playset') : '-',
            'article_number' => str_pad($article->number ?? '-', 10, ' ', STR_PAD_RIGHT),
            'cardmarket_number' => str_pad(Arr::get($cardmarket_article, 'number_from_cardmarket_comments', '-'), 10, ' ', STR_PAD_RIGHT),
            'cardmarket_comments' => Arr::get($cardmarket_article, 'cardmarket_comments', '-'),
        ];
    }

    private function addToCsvFile(array $output): void
    {
        fputcsv($this->csv_file_handle, array_map(fn($item) => trim($item), $output), ';');
    }
}
