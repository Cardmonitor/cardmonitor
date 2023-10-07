<?php

namespace App\Console\Commands\Order;

use App\User;
use App\Models\Orders\Order;
use Illuminate\Console\Command;
use App\Support\BackgroundTasks;
use App\Notifications\FlashMessage;
use Illuminate\Support\Facades\App;

class SyncCommand extends Command
{
    protected $signature = 'order:sync
        {user}
        {--actor=seller}
        {--state=received}
        {--order=}';

    protected $description = 'Add/Update orders from cardmarket API';

    protected User $user;

    public function handle(BackgroundTasks $BackgroundTasks)
    {
        $this->user = User::find($this->argument('user'));
        $cardmarket_order_id = $this->option('order');

        if (isset($cardmarket_order_id)) {
            $this->syncOrder($cardmarket_order_id, Order::FORCE_UPDATE_OR_CREATE);
            return self::SUCCESS;
        }

        try {
            $backgroundtask_key = 'user.' . $this->user->id . '.order.sync';
            $BackgroundTasks->put($backgroundtask_key, 1);

            $orders = Order::where('user_id', $this->user->id)->where('source_slug', 'cardmarket')->state($this->option('state'))->get();
            $order_source_ids = $orders->pluck('source_id');

            $synced_orders = $this->user->cardmarketApi->syncOrders($this->option('actor'), $this->option('state'));

            $not_synced_orders = $order_source_ids->diff($synced_orders);
            foreach ($not_synced_orders as $cardmarket_order_id) {
                $this->syncOrder($cardmarket_order_id);
            }

            $BackgroundTasks->forget($backgroundtask_key);
            $this->user->notify(FlashMessage::success('Die Bestellungen im Status <b>' . $this->option('state') . '</b> wurden synchronisiert.', [
                'background_tasks' => App::make(BackgroundTasks::class)->all(),
            ]));

            return self::SUCCESS;
        }
        catch (\Exception $e) {
            $BackgroundTasks->forget($backgroundtask_key);
            $this->user->notify(FlashMessage::danger('Die Bestellungen im Status <b>' . $this->option('state') . '</b> konnten nicht synchronisiert werden.', [
                'background_tasks' => App::make(BackgroundTasks::class)->all(),
            ]));
            return self::FAILURE;
        }
    }

    private function syncOrder(int $cardmarket_order_id, bool $force = false): void
    {
        $cardmarket_order = $this->user->cardmarketApi->order->get($cardmarket_order_id);
        if (empty($cardmarket_order)) {
            return;
        }
        Order::updateOrCreateFromCardmarket($this->user->id, $cardmarket_order['order'], $force);
    }
}
