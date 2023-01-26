<?php

namespace App\Console\Commands\StoringHistory;

use App\Models\Articles\Article;
use App\Models\Articles\StoringHistory;
use App\User;
use App\Models\Cards\Card;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\LazyCollection;

class InitialCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storing-history:init
        {user}
        {--skryfall : Syncs all cards from Skryfall.}
        {--reset-storing-histories : Resets all storing histories.}
        {--reset-numbers : Resets all numbers.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the initial storing histories.';

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
        $user = User::findOrFail($this->argument('user'));

        if ($this->option('skryfall')) {
            Artisan::call('card:skryfall:sync', [
                '--user' => $user->id,
            ]);
        }

        $this->resetNumbers($user);
        $this->resetStoringHistories($user);

        $number = Article::maxNumber($user->id);
        $storing_history_id = null;
        $last_card_color_order_by = '';
        $articles = $this->getArticles($user);
        foreach ($articles as $article) {

            if ($last_card_color_order_by != $article->card->color_order_by) {
                $storing_history_id = StoringHistory::create([
                    'user_id' => $user->id,
                ])->id;
                $last_card_color_order_by = $article->card->color_order_by;
            }

            $number = Article::incrementNumber($number);
            $article->update([
                'number' => $number,
                'storing_history_id' => $storing_history_id,
            ]);
            $this->line($article->card->color_order_by . "\t" . $article->card->cmc . "\t" . $number . "\t" . $storing_history_id  . "\t" . $article->card->expansion->abbriviation. "\t" . $article->card->name);
        }

        return self::SUCCESS;
    }

    private function getArticles(User $user): LazyCollection
    {
        $query = $user->articles()
            ->select('articles.*')
            ->join('cards', 'articles.card_id', '=', 'cards.id')
            ->with([
                'card' => function ($query) {
                    return $query->wtih('expansion')
                        ->whereHas('expansion');
                },
            ])
            ->whereNull('articles.number')
            ->whereNull('articles.storing_history_id')
            ->sold(0)
            ->orderBy('cards.color_order_by', 'ASC')
            ->orderBy('cards.cmc', 'ASC')
            ->orderBy('cards.name', 'ASC');

        return $query->cursor();
    }

    private function resetNumbers(User $user)
    {
        if (! $this->option('reset-numbers')) {
            return;
        }

        $user->articles()
            ->update([
                'number' => null,
        ]);
    }

    private function resetStoringHistories(User $user)
    {
        if (! $this->option('reset-storing-histories')) {
            return;
        }

        $user->articles()
            ->update([
                'storing_history_id' => null,
        ]);

        $user->storingHistories()->delete();
    }
}
