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

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:sync {user} {--actor=seller}  {--state=received}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add/Update received orders from cardmarket API';

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

        try {
            $this->processing();
            $orders = Order::where('user_id', $this->user->id)->state($this->option('state'))->get();
            $orderIds = $orders->pluck('id');
            $syncedOrders = $this->user->cardmarketApi->syncOrders($this->option('actor'), $this->option('state'));
            $notSyncedOrders = $orderIds->diff($syncedOrders);
            foreach ($notSyncedOrders as $key => $orderId) {
                $cardmarketOrder = $this->user->cardmarketApi->order->get($orderId);
                Order::updateOrCreateFromCardmarket($this->user->id, $cardmarketOrder['order']);
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
