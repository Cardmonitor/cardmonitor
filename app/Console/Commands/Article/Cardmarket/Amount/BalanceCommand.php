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
        {--excecute : increase and decreae the amount of articles}';

    protected $description = 'Updates amaount of similar articles on Cardmarket.';

    protected User $user;

    public function handle(BackgroundTasks $BackgroundTasks)
    {
        $this->user = User::with('api')->findOrFail($this->argument('user'));

        $backgroundtask_key = 'user.' . $this->user->id . '.article.cardmarket.amaount.balance';
        $BackgroundTasks->put($backgroundtask_key, 1);

        $articles_count = 0;
        $updated_count = 0;

        $cards = $this->getCardIdsToDecrement($this->argument('amount'));
        foreach ($cards as $card) {
            echo $card->card_id . "\t" . $card->articles_count . "\t" . $card->cardmarket_count . "\t" . $card->abbreviation . "\t" . $card->local_name . "\t" . $card->language_id . "\t" . $card->condition . "\t" . $card->is_foil . "\t" . $card->is_signed . "\t" . $card->is_altered . "\t" . $card->is_playset . "\t" . $card->is_first_edition . "\t" . $card->is_reverse_holo . "\t" . $card->unit_price;
            echo PHP_EOL;

            $articles = $this->getArticlesForCard($card);
            foreach ($articles as $article_counter => $article) {
                echo $article_counter . "\t" . $article->id . "\t" . $article->number . "\t" . $article->externalIdsCardmarket?->external_id  . "\t\t\t";

                if ($article_counter < $this->argument('amount')) {
                    echo 'SKIP' . PHP_EOL;
                    continue;
                }

                if ($this->option('excecute')) {
                    $is_deleted = $article->syncDelete();
                    echo $is_deleted ? 'DELETED' : 'ERROR';
                    usleep(100000); // 0.1 seconds
                }
                else {
                    echo 'TO DELETE';
                }

                echo PHP_EOL;
            }
        }

        return self::SUCCESS;

        $this->line('Updated ' . $updated_count . '/' . $articles_count . ' articles.');

        $BackgroundTasks->forget($backgroundtask_key);

        $this->notifyUser($articles_count, $updated_count);

        return self::SUCCESS;
    }

    private function getCardIdsToDecrement(int $target_amount): LazyCollection
    {
        return $this->getQueryToBalanceAmount()
            ->having('cardmarket_count', '>', $this->argument('amount'))
            ->cursor();
    }

    private function getCardIdsToIncrement(int $target_amount): LazyCollection
    {
        return $this->getQueryToBalanceAmount()
            ->having('cardmarket_count', '<', $this->argument('amount'))
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

    private function notifyUser(int $articles_count, int $updated_count): void
    {
        $this->user->notify(FlashMessage::success($updated_count .'/' . $articles_count . ' Artikel ' . ($updated_count === 1 ? 'wurde' : 'wurden') . ' zu Cardmarket hochgeladen', [
            'background_tasks' => App::make(BackgroundTasks::class)->all(),
        ]));
    }
}
