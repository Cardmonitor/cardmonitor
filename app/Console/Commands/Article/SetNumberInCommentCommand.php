<?php

namespace App\Console\Commands\Article;

use App\Models\Articles\Article;
use App\User;
use Illuminate\Console\Command;

class SetNumberInCommentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:set-number-in-comment
        {user}
        {--update : Update cardmarket_comments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the number in the cardmarket_comments of the article.';

    protected User $user;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->user = User::findOrFail($this->argument('user'));
        $updated_count = 0;

        $articles = $this->user->articles()->whereNotNull('number')->sold(0)->whereNotNull('cardmarket_article_id')->cursor();
        foreach ($articles as $article) {
            $number_from_cardmarket_comments = Article::numberFromCardmarketComments($article->cardmarket_comments);
            $this->output->write($article->id . "\t" . $article->number . "\t" . ($number_from_cardmarket_comments ?: 'NICHT VORHANDEN') . "\t");

            if (empty($number_from_cardmarket_comments) || $number_from_cardmarket_comments != $article->number) {
                $article->setNumberInCardmarketComments();
                $updated_count++;
                $this->output->write($article->cardmarket_comments . "\t");
            }

            if ($this->option('update')) {
                $updated = $article->save();
                $this->output->write('UPDATE' . ($updated ? '' : ' FAILED'));
            }

            $this->output->writeln('');
        }

        $this->line('Updated ' . $updated_count . ' articles');

        return self::SUCCESS;
    }
}
