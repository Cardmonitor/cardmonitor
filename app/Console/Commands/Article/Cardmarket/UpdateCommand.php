<?php

namespace App\Console\Commands\Article\Cardmarket;

use App\User;
use Illuminate\Console\Command;
use App\Support\BackgroundTasks;
use App\Notifications\FlashMessage;
use Illuminate\Support\Facades\App;

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
        {--articles=* : ids of the articles to update}
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
    public function handle(BackgroundTasks $BackgroundTasks)
    {
        $this->user = User::with('api')->findOrFail($this->argument('user'));

        $backgroundtask_key = 'user.' . $this->user->id . '.article.cardmarket.update';
        $BackgroundTasks->put($backgroundtask_key, 1);

        $CardmarketApi = $this->user->cardmarketApi;
        $updated_count = 0;

        foreach ($this->getArticles() as $article) {
            $this->output->write($article->id . "\t" . $article->number . "\t" . $article->card->expansion->abbreviation . "\t" . $article->local_name  . "\t\t\t");

            // Es gibt keine eindeutige Fehlermeldung mehr, deshalb wird das Ergbis der Anfrage hier nicht ausgewertet
            $article->sync();
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

        $BackgroundTasks->forget($backgroundtask_key);

        $this->notifyUser($updated_count);

        return self::SUCCESS;
    }

    private function getArticles(): \Illuminate\Support\LazyCollection
    {
        $query = $this->user->articles()
            ->with('card.expansion')
            ->whereNotNull('number')
            ->sold(0)
            ->oldest('synced_at');

        if ($this->option('article')) {
            $query->where('id', $this->option('article'));
        }

        if ($this->option('articles')) {
            $query->whereIn('id', $this->option('articles'));
        }
        else {
            $query->whereNotNull('cardmarket_article_id');
        }

        if ($this->option('limit')) {
            $query->limit($this->option('limit'));
        }

        return $query->cursor();
    }

    private function notifyUser(int $updated_count): void
    {
        $this->user->notify(FlashMessage::success($updated_count . ' Artikel ' . ($updated_count === 1 ? 'wurde' : 'wurden') . ' zu Cardmarket hochgeladen', [
            'background_tasks' => App::make(BackgroundTasks::class)->all(),
        ]));
    }
}
