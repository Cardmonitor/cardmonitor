<?php

namespace App\Console\Commands\Order;

use App\Models\Apis\Api;
use App\Models\Articles\Article;
use App\Models\Cards\Card;
use App\Models\Orders\Order;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class WithoutArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:without_articles {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update orders from cardmarket API where articles count is zero';

    protected User $user;

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
        $this->user = User::find($this->argument('user'));

        $orders = $this->user->orders()->whereDoesntHave('articles')->get();
        $this->line($orders->count() . ' orders without articles');
        try {
            $this->processing();

            foreach ($orders as $key => $order) {
                $this->line('Order ID: ' . $order->id);
                $cardmarketOrder = $this->user->cardmarketApi->order->get($order->id);
                Order::updateOrCreateFromCardmarket($this->user->id, $cardmarketOrder['order'], Order::FORCE_UPDATE_OR_CREATE);
            }
        }
        finally {
            $this->processed();
        }
    }

    public function processing()
    {
        $this->user->update([
            'is_syncing_orders' => true,
        ]);
    }

    public function processed()
    {
        $this->user->update([
            'is_syncing_orders' => false,
        ]);
    }
}
