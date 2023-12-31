<?php

namespace App\Console\Commands\WooCommerce\Products;

use App\User;
use Illuminate\Console\Command;

class DeleteCommand extends Command
{
    protected $signature = 'woocommerce:products:delete
        {user}
        {--article= : id of the article to delete}
        {--articles=* : ids of the articles to delete}
        {--limit= : amount of articles to delete}';

    protected $description = 'Deletes the articles on WooCommerce.';

    private User $user;

    public function handle()
    {
        $this->user = User::findOrFail($this->argument('user'));

        $articles_count = 0;
        $deleted_count = 0;

        foreach ($this->getArticles() as $article) {
            $this->output->write($article->id . "\t" . $article->number . "\t" . $article->card->expansion->abbreviation . "\t" . $article->local_name  . "\t\t\t");

            if ($article->syncWooCommerceDelete()) {
                $deleted_count++;
            }
            $articles_count++;

            $this->output->writeln('');
        }

        $this->line('Deleted ' . $deleted_count . '/' . $articles_count . ' articles.');

        return self::SUCCESS;
    }

    private function getArticles(): \Illuminate\Support\LazyCollection
    {
        $query = $this->user->articles()
            ->with('card.expansion')
            ->whereNotNull('number')
            ->oldest('synced_at');

        if ($this->option('article')) {
            $query->where('id', $this->option('article'));
        }

        if ($this->option('articles')) {
            $query->whereIn('id', $this->option('articles'));
        }

        if ($this->option('limit')) {
            $query->limit($this->option('limit'));
        }

        return $query->cursor();
    }
}
