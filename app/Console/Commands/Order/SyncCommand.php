<?php

namespace App\Console\Commands\Order;

use App\Models\Orders\Order;
use App\User;
use Illuminate\Console\Command;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:sync {user} {--actor=seller} {--state=received} {--order=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add/Update orders from cardmarket API';

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
        $cardmarket_order_id = $this->option('order');

        if (isset($cardmarket_order_id)) {
            $this->syncOrder($cardmarket_order_id, Order::FORCE_UPDATE_OR_CREATE);
            return self::SUCCESS;
        }

        try {
            $this->processing();

            $orders = Order::where('user_id', $this->user->id)->where('source_slug', 'cardmarket')->state($this->option('state'))->get();
            $order_source_ids = $orders->pluck('source_id');

            $synced_orders = $this->user->cardmarketApi->syncOrders($this->option('actor'), $this->option('state'));

            $not_synced_orders = $order_source_ids->diff($synced_orders);
            foreach ($not_synced_orders as $cardmarket_order_id) {
                $this->syncOrder($cardmarket_order_id);
            }
        }
        finally {
            $this->processed();
        }

        return self::SUCCESS;
    }

    private function syncOrder(int $cardmarket_order_id, bool $force = false): void
    {
        $cardmarket_order = $this->user->cardmarketApi->order->get($cardmarket_order_id);
        Order::updateOrCreateFromCardmarket($this->user->id, $cardmarket_order['order'], $force);
    }

    private function processing()
    {
        $this->user->update([
            'is_syncing_orders' => true,
        ]);
    }

    private function processed()
    {
        $this->user->update([
            'is_syncing_orders' => false,
        ]);
    }
}
