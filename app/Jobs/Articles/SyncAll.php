<?php

namespace App\Jobs\Articles;

use App\Models\Expansions\Expansion;
use App\Models\Games\Game;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->processing();
            // $this->user->cardmarketApi->syncAllSellerOrders();
            foreach (Game::keyValue() as $gameId => $name) {
                $this->user->cardmarketApi->syncAllArticles($gameId);
            }
            $this->processed();
        }
        catch (\Exception $e) {
            $this->processed();

            throw $e;
        }
    }

    public function processing()
    {
        $this->user->update([
            'is_syncing_articles' => true,
        ]);
    }

    public function processed()
    {
        $this->user->update([
            'is_syncing_articles' => false,
        ]);
    }
}
