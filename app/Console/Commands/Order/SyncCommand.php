<?php

namespace App\Console\Commands\Order;

use App\Enums\ExternalIds\ExternalType;
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
        {--state=}
        {--states=*}
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

        $backgroundtask_keys = [
            'user.' . $this->user->id . '.order.sync.status',
            'user.' . $this->user->id . '.order.sync.cardmarket',
        ];

        try {

            foreach ($backgroundtask_keys as $backgroundtask_key) {
                $BackgroundTasks->put($backgroundtask_key, 1);
            }

            $states = $this->getStates();
            foreach ($states as $state) {
                $this->handleState($this->option('actor'), $state);
            }

            foreach ($backgroundtask_keys as $backgroundtask_key) {
                $BackgroundTasks->forget($backgroundtask_key);
            }

            $this->user->notify(FlashMessage::success('Die Bestellungen im Status <b>' . implode(', ', $states) . '</b> wurden synchronisiert.', [
                'background_tasks' => App::make(BackgroundTasks::class)->all(),
            ]));

            return self::SUCCESS;
        }
        catch (\Exception $e) {
            foreach ($backgroundtask_keys as $backgroundtask_key) {
                $BackgroundTasks->forget($backgroundtask_key);
            }
            $this->user->notify(FlashMessage::danger('Die Bestellungen im Status <b>' . implode(', ', $states) . '</b> konnten nicht synchronisiert werden.', [
                'background_tasks' => App::make(BackgroundTasks::class)->all(),
            ]));
            return self::FAILURE;
        }
    }

    private function getStates(): array
    {
        $states = $this->option('states');
        if ($this->option('state')) {
            $states[] = $this->option('state');
        }

        return $states;
    }

    private function handleState(string $actor, string $state): void
    {
        $orders = Order::where('user_id', $this->user->id)->where('source_slug', ExternalType::CARDMARKET->value)->state($state)->get();
        $order_source_ids = $orders->pluck('source_id');

        $synced_orders = $this->user->cardmarketApi->syncOrders($actor, $state);

        $not_synced_orders = $order_source_ids->diff($synced_orders);
        foreach ($not_synced_orders as $cardmarket_order_id) {
            $this->syncOrder($cardmarket_order_id);
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
