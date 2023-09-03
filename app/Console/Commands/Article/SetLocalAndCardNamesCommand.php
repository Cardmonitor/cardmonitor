<?php

namespace App\Console\Commands\Article;

use App\Models\Articles\Article;
use App\User;
use Illuminate\Console\Command;

class SetLocalAndCardNamesCommand extends Command
{
    protected $signature = 'article:set-local-and-card-names
        {--update : Update local and card names in database}';

    protected $description = 'Sets the local and card names of the article.';

    public function handle()
    {
        $this->info('Starting to set local and card names of articles.');
        $articles = Article::with([
            'card.locatizations',
        ])
            ->cursor();

        $this->line($this->fixedLength('Article ID', 10) . "\t" . $this->fixedLength('Local Name') . "\t" . $this->fixedLength('Card Name') . "\t" . $this->fixedLength('Card ID', 10) . "\t" . 'Updated');
        foreach ($articles as $article) {
            $card_name = $article->card->name;
            $local_name = $article->getLocalName();
            $is_updated = false;
            if ($this->option('update')) {
                $is_updated = $article->update([
                    'local_name' => $local_name,
                    'card_name' => $card_name,
                ]);
            }
            $this->line($this->fixedLength($article->id, 10) . "\t" . $this->fixedLength($local_name) . "\t" . $this->fixedLength($card_name) . "\t" . $this->fixedLength($article->card_id, 10) . "\t" . $is_updated);
        }
        \Illuminate\Support\Str::slug('foo bar');
        return self::SUCCESS;
    }

    private function fixedLength($string, $length = 30)
    {
        return str_pad(substr($string, 0, $length), $length);
    }
}
