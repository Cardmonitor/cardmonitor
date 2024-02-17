<?php

namespace App\Console\Commands\Order\WooCommerce;

use App\User;
use App\Enums\Orders\Status;
use App\Models\Orders\Order;
use Illuminate\Console\Command;
use App\Support\BackgroundTasks;
use App\Notifications\FlashMessage;
use Illuminate\Support\Facades\App;
use App\Enums\ExternalIds\ExternalType;
use App\Importers\Orders\WooCommerceOrderImporter;

class ImportCommand extends Command
{
    protected $signature = 'order:woocommerce:import
        {user}
        {--states=*}
        {--order= : WooCommerce Order ID}';

    protected $description = 'Import Orders from WooCommerce API';

    private User $user;

    public function handle(BackgroundTasks $BackgroundTasks)
    {
        $this->user = User::findOrFail($this->argument('user'));

        try {
            if (!$this->option('order')) {
                $backgroundtask_key = 'user.' . $this->user->id . '.order.sync';
                $BackgroundTasks->put($backgroundtask_key, 1);
            }

            $woocommerce_states = $this->option('states');
            $order_source_ids = collect();
            foreach ($woocommerce_states as $woocommerce_state) {
                $state = Status::fromWooCommerceSlug($woocommerce_state)->value;
                $orders = Order::where('user_id', $this->user->id)->where('source_slug', ExternalType::WOOCOMMERCE->value)->state($state)->get();
                $order_source_ids->concat($orders->pluck('source_id'));
            }

            $orders = $this->getWooCommerceOrders();
            $synced_orders = [];
            foreach ($orders as $order) {
                $order = WooCommerceOrderImporter::import($this->user->id, $order);
                $synced_orders[] = $order->source_id;
            }

            $not_synced_orders = $order_source_ids->diff($synced_orders);
            foreach ($not_synced_orders as $woocommerce_order_id) {
                $order = WooCommerceOrderImporter::import($this->user->id, $this->getWooCommerceOrder($woocommerce_order_id));
            }

            if (!$this->option('order')) {
                $BackgroundTasks->forget($backgroundtask_key);
                $this->user->notify(FlashMessage::success('Die WooCommerce Bestellungen im Status <b>' . implode(', ', $woocommerce_states) . '</b> wurden synchronisiert.', [
                    'background_tasks' => App::make(BackgroundTasks::class)->all(),
                ]));
            }

            return self::SUCCESS;
        }
        catch (\Exception $e) {
            $BackgroundTasks->forget($backgroundtask_key);
            $this->user->notify(FlashMessage::danger('Die WooCommerce Bestellungen im Status <b>' . implode(', ', $woocommerce_states) . '</b> konnten nicht synchronisiert werden.', [
                'background_tasks' => App::make(BackgroundTasks::class)->all(),
            ]));
            return self::FAILURE;
        }
    }

    private function getWooCommerceOrder(int $woocommerce_order_id): array
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerceOrder();
        $response = $WooCommerce->order($woocommerce_order_id);

        return $response->json();
    }

    private function getWooCommerceOrders(): array
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerceOrder();

        if ($this->option('order')) {
            $response = $WooCommerce->order($this->option('order'));
            return [
                $response->json()
            ];
        }

        if ($this->option('states')) {
            $response = $WooCommerce->orders([
                'status' => implode(',', $this->option('states')),
            ]);
            return $response->json();
        }

        return [];
    }
}
