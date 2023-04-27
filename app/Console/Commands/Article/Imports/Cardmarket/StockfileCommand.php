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

    protected $user;

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
        $this->user = User::find($this->argument('user'));
        $path = storage_path('app/' . $this->user->id . '-stock-' . Game::ID_MAGIC . '.csv');
        $Stockfile = new \App\Importers\Articles\Cardmarket\Stockfile($this->user->id, $path, Game::ID_MAGIC);
        $cardmarket_cards = $Stockfile->setCardmarketCards();
        $stockfile_article_count = 0;

        $import_states = [
            'NUMBER' => 0,
            'CARDMARKET_ID' => 0,
            'SIMILAR' => 0,
            'CARD' => 0,
            'DELETED' => 0,
            'CREATED' => 0,
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
                    $this->line($cardmarket_product_id . "\t" . $cardmarket_article['cardmarket_article_id'] . "\t" . $article->id . "\t" . $import_state);
                    $articles_for_card->forget($article->id);
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
                    $this->line($cardmarket_product_id . "\t" . $cardmarket_article['cardmarket_article_id'] . "\t" . $article->id . "\t" . $import_state);
                    $articles_for_card->forget($article->id);
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
                    $this->line($cardmarket_product_id . "\t" . $cardmarket_article['cardmarket_article_id'] . "\t" . $article->id . "\t" . $import_state);
                    $articles_for_card->forget($article->id);
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
                    $this->line($cardmarket_product_id . "\t" . $cardmarket_article['cardmarket_article_id'] . "\t" . $article->id . "\t" . $import_state);
                    $articles_for_card->forget($article->id);
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
                    $this->line($cardmarket_product_id . "\t" . $cardmarket_article['cardmarket_article_id'] . "\t" . '0' . "\t" . $import_state);
                    $this->line(Arr::get($cardmarket_article, 'language_id', ''));
                    $this->line(Arr::get($cardmarket_article, 'condition', ''));
                    $this->line((int) Arr::get($cardmarket_article, 'is_foil', false));
                    $this->line((int) Arr::get($cardmarket_article, 'is_signed', false));
                    $this->line((int) Arr::get($cardmarket_article, 'is_altered', false));
                    $this->line((int) Arr::get($cardmarket_article, 'is_playset', false));
                    $this->line(Arr::get($cardmarket_article, 'number_from_cardmarket_comments', ''));
                    $import_states[$import_state]++;
                }

            }

            $import_state = 'DELETED';
            $articles = $articles_for_card;
            foreach ($articles as $article) {
                $this->line($cardmarket_product_id . "\t" . 0 . "\t" . $article->id . "\t" . $import_state);
                $this->line($article->language_id);
                $this->line($article->condition);
                $this->line($article->is_foil);
                $this->line($article->is_signed);
                $this->line($article->is_altered);
                $this->line($article->is_playset);
                if ($article->number) {
                    $this->line($article->number);
                }
                $articles_for_card->forget($article->id);
                $import_states[$import_state]++;
            }
        }

        foreach ($import_states as $import_state => $count) {
            $this->line($import_state . ': ' . $count);
        }

        $article_count = Article::where('user_id', $this->user->id)->whereNull('sold_at')->whereNotNull('cardmarket_article_id')->count();

        $this->line('---');
        $this->line('Stockfile Article Count: ' . $stockfile_article_count);
        $this->line('Database Article Count: ' . $article_count);
        $this->line('Difference: ' . ($stockfile_article_count - $article_count));
        $this->line('Database Article Count Bereinigt: ' . $article_count - $import_states['DELETED'] + $import_states['CREATED']);
    }
}
