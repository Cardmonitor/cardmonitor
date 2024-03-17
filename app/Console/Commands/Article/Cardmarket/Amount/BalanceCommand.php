<?php

namespace App\Console\Commands\Article\Cardmarket\Amount;

use App\Enums\ExternalIds\ExternalType;
use App\User;
use Illuminate\Console\Command;
use App\Support\BackgroundTasks;
use Illuminate\Support\Facades\DB;
use App\Notifications\FlashMessage;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\LazyCollection;
use stdClass;

class BalanceCommand extends Command
{
    protected $signature = 'article:cardmarket:amount:balance
        {user}
        {amount : amount of articles to balance to}
        {--execute : increase and decreae the amount of articles}';

    protected $description = 'Updates amount of similar articles on Cardmarket.';

    protected User $user;

    public function handle(BackgroundTasks $BackgroundTasks)
    {
        $this->user = User::with('api')->findOrFail($this->argument('user'));

        $backgroundtask_key = 'user.' . $this->user->id . '.article.cardmarket.amount.balance';
        $BackgroundTasks->put($backgroundtask_key, 1);

        $deleted_count = $this->decrement($this->argument('amount'));
        $created_count = $this->increment($this->argument('amount'));

        $this->line('Deleted ' . $deleted_count . ' articles.');
        $this->line('Created ' . $created_count . ' articles.');

        $BackgroundTasks->forget($backgroundtask_key);

        $this->notifyUser($deleted_count, $created_count);

        return self::SUCCESS;
    }

    private function decrement(int $target_amount): int
    {
        $deleted_count = 0;

        $cards = $this->getCardIdsToDecrement($target_amount);
        foreach ($cards as $card) {
            echo $card->card_id . "\t" . $card->articles_count . "\t" . $card->cardmarket_count . "\t" . $card->abbreviation . "\t" . $card->local_name . "\t" . $card->language_id . "\t" . $card->condition . "\t" . $card->is_foil . "\t" . $card->is_signed . "\t" . $card->is_altered . "\t" . $card->is_playset . "\t" . $card->is_first_edition . "\t" . $card->is_reverse_holo . "\t" . $card->unit_price;
            echo PHP_EOL;

            $articles = $this->getArticlesForCard($card);
            foreach ($articles as $article_counter => $article) {
                echo $article_counter . "\t" . $article->id . "\t" . $article->number . "\t" . str_pad($article->externalIdsCardmarket?->external_id ?? '-', 12, ' ', STR_PAD_RIGHT)  . "\t\t\t";

                if ($article_counter < $target_amount) {
                    echo 'SKIP' . PHP_EOL;
                    continue;
                }

                if ($this->option('execute')) {
                    $is_deleted = $article->syncDelete();
                    echo $is_deleted ? 'DELETED' : 'ERROR';
                    usleep(100000); // 0.1 seconds
                }
                else {
                    echo 'TO DELETE';
                }

                $deleted_count++;

                echo PHP_EOL;
            }
        }

        return $deleted_count;
    }

    private function getCardIdsToDecrement(int $target_amount): LazyCollection
    {
        return $this->getQueryToBalanceAmount()
            ->having('cardmarket_count', '>', $target_amount)
            ->cursor();
    }

    private function increment(int $target_amount): int
    {
        $created_count = 0;

        $cards = $this->getCardIdsToIncrement($target_amount);
        foreach ($cards as $card) {
            echo $card->card_id . "\t" . $card->articles_count . "\t" . $card->cardmarket_count . "\t" . $card->abbreviation . "\t" . $card->local_name . "\t" . $card->language_id . "\t" . $card->condition . "\t" . $card->is_foil . "\t" . $card->is_signed . "\t" . $card->is_altered . "\t" . $card->is_playset . "\t" . $card->is_first_edition . "\t" . $card->is_reverse_holo . "\t" . $card->unit_price;
            echo PHP_EOL;

            $articles = $this->getArticlesForCard($card);
            foreach ($articles as $article_counter => $article) {
                echo $article_counter . "\t" . $article->id . "\t" . $article->number . "\t" . str_pad($article->externalIdsCardmarket?->external_id ?? '-', 12, ' ', STR_PAD_RIGHT)  . "\t\t\t";

                if ($article->externalIdsCardmarket?->external_id) {
                    echo 'SKIP' . PHP_EOL;
                    continue;
                }

                if ($article_counter >= $target_amount) {
                    echo 'SKIP' . PHP_EOL;
                    continue;
                }

                if ($this->option('execute')) {
                    $is_created = $article->sync();
                    echo $is_created ? 'CREATED' : 'ERROR';
                    usleep(100000); // 0.1 seconds
                }
                else {
                    echo 'TO CREATE';
                }

                $created_count++;

                echo PHP_EOL;
            }
        }

        return $created_count;
    }

    private function getCardIdsToIncrement(int $target_amount): LazyCollection
    {
        return $this->getQueryToBalanceAmount()
            ->having('cardmarket_count', '<', $target_amount)
            ->havingRaw(DB::raw('articles_count') . ' > ' . DB::raw('cardmarket_count'))
            ->cursor();
    }

    private function getQueryToBalanceAmount(): Builder
    {
        return DB::table('articles')
            ->select('articles.card_id', 'articles.local_name', 'expansions.abbreviation', 'articles.language_id', 'articles.condition', 'articles.is_foil', 'articles.is_signed', 'articles.is_altered', 'articles.is_playset', 'articles.is_first_edition', 'articles.is_reverse_holo', 'articles.unit_price', DB::raw('count(articles.id) AS articles_count'), DB::raw('count(articles_external_ids.id) AS cardmarket_count'))
            ->join('cards', 'articles.card_id', '=', 'cards.id')
            ->join('expansions', 'cards.expansion_id', '=', 'expansions.id')
            ->leftJoin('articles_external_ids', function ($join) {
                $join->on('articles.id', '=', 'articles_external_ids.article_id')
                ->where('articles_external_ids.external_type', ExternalType::CARDMARKET->value)
                ->whereNotNull('articles_external_ids.external_id');
            })
            ->where('articles.user_id', $this->user->id)
            ->whereNotNull('articles.number')
            ->whereNotNull('articles.storing_history_id') // Notwendig?
            ->where('articles.is_sellable', 1)
            ->groupBy('articles.card_id', 'articles.language_id', 'articles.condition', 'articles.is_foil', 'articles.is_signed', 'articles.is_altered', 'articles.is_playset', 'articles.is_first_edition', 'articles.is_reverse_holo', 'articles.unit_price');
    }

    private function getArticlesForCard(stdClass $card): \Illuminate\Support\LazyCollection
    {
        $query = $this->user->articles()
            ->with([
                'card.expansion',
                'externalIdsCardmarket',
            ])
            ->where('card_id', $card->card_id)
            ->whereNotNull('number')
            ->isSellable(1)
            ->where('language_id', $card->language_id)
            ->where('condition', $card->condition)
            ->where('is_foil', $card->is_foil)
            ->where('is_signed', $card->is_signed)
            ->where('is_altered', $card->is_altered)
            ->where('is_playset', $card->is_playset)
            ->where('is_first_edition', $card->is_first_edition)
            ->where('is_reverse_holo', $card->is_reverse_holo)
            ->where('unit_price', $card->unit_price);

        return $query->cursor();
    }

    private function notifyUser(int $deleted_count, int $created_count): void
    {
        $this->user->notify(FlashMessage::success($created_count .' Artikel ' . ($created_count === 1 ? 'wurde' : 'wurden') . ' zu Cardmarket hochgeladen.<br />' . $deleted_count . ' Artikel ' . ($deleted_count === 1 ? 'wurde' : 'wurden') . ' auf Cardmarket gelöscht.', [
            'background_tasks' => App::make(BackgroundTasks::class)->all(),
        ]));
    }
}
