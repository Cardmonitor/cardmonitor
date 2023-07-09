<?php

namespace App\Console\Commands\Article\Cardmarket;

use App\User;
use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:cardmarket:update
        {user}
        {--article= : id of the article to update}
        {--limit= : amount of articles to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the articles to Cardmarket.';

    protected User $user;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->user = User::with('api')->findOrFail($this->argument('user'));
        $CardmarketApi = $this->user->cardmarketApi;
        $updated_count = 0;

        foreach ($this->getArticles() as $article) {
            $this->output->write($article->id . "\t" . $article->number . "\t" . $article->card->expansion->abbreviation . "\t" . $article->local_name  . "\t\t\t");

            // Es gibt keine eindeutige Fehlermeldung mehr, deshalb wird das Ergbis der Anfrage hier nicht ausgewertet
            $response = $CardmarketApi->stock->update([$article->toCardmarket()]);
            $article->update([
                'synced_at' => now(),
                'has_sync_error' => false,
                'sync_error' => null,
                'should_sync' => false,
            ]);
            $updated_count++;

            $this->output->writeln('');
        }

        $this->line('Updated ' . $updated_count . ' articles.');

        return self::SUCCESS;
    }

    private function getArticles(): \Illuminate\Support\LazyCollection
    {
        $query = $this->user->articles()
            ->with('card.expansion')
            ->whereNotNull('number')
            ->sold(0)
            ->whereNotNull('cardmarket_article_id')
            ->oldest('synced_at');

        if ($this->option('article')) {
            $query->where('id', $this->option('article'));
        }

        if ($this->option('limit')) {
            $query->limit($this->option('limit'));
        }

        return $query->cursor();
    }
}
