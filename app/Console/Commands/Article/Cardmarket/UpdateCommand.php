<?php

namespace App\Console\Commands\Article\Cardmarket;

use App\Models\Articles\Article;
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
        $this->user = User::findOrFail($this->argument('user'));
        $updated_count = [
            'success' => 0,
            'failed' => 0,
        ];

        $query = $this->user->articles()->with('card.expansion')->whereNotNull('number')->sold(0)->whereNotNull('cardmarket_article_id')->oldest('synced_at');

        if ($this->option('limit')) {
            $query->limit($this->option('limit'));
        }

        $articles = $query->cursor();
        foreach ($articles as $article) {
            $this->output->write($article->id . "\t" . $article->number . "\t" . $article->card->expansion->abbreviation . "\t" . $article->local_name  . "\t\t\t");

            $updated = $article->syncUpdate();
            $state = $updated ? 'success' : 'failed';
            $this->output->write($state);

            $updated_count[$state]++;
            $this->output->writeln('');
        }

        foreach ($updated_count as $state => $count) {
            $this->line($state . ': ' . $count . ' articles');
        }

        return self::SUCCESS;
    }
}
