<?php

namespace App\Console\Commands\Order\WooCommerce;

use App\User;
use App\Enums\Orders\Status;
use App\Models\Orders\Order;
use Illuminate\Console\Command;
use App\Support\BackgroundTasks;
use Illuminate\Support\Collection;
use App\Notifications\FlashMessage;
use Illuminate\Support\Facades\App;
use PhpParser\ErrorHandler\Collecting;
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
        $backgroundtask_keys = [
            'user.' . $this->user->id . '.order.sync.status',
            'user.' . $this->user->id . '.order.sync.woocommerce',
        ];

        try {
            $this->putBackgroundTask($BackgroundTasks, $backgroundtask_keys);

            $woocommerce_states = $this->option('states');
            $order_source_ids = $this->getOrderSourceIdsForStates($woocommerce_states);

            $orders = $this->getWooCommerceOrders();
            $imported_order_source_ids = [];
            foreach ($orders as $order) {
                $order = WooCommerceOrderImporter::import($this->user->id, $order);
                $imported_order_source_ids[] = $order->source_id;
            }

            $this->importNotImportedOrders($order_source_ids->diff($imported_order_source_ids));
            $this->notifyUserSuccess($BackgroundTasks, $backgroundtask_keys, $woocommerce_states);

            return self::SUCCESS;
        }
        catch (\Exception $e) {
            foreach ($backgroundtask_keys as $backgroundtask_key) {
                $BackgroundTasks->forget($backgroundtask_key);
            }
            $this->user->notify(FlashMessage::danger('Die WooCommerce Bestellungen im Status <b>' . implode(', ', $woocommerce_states) . '</b> konnten nicht importiert werden.', [
                'background_tasks' => App::make(BackgroundTasks::class)->all(),
            ]));
            return self::FAILURE;
        }
    }

    private function putBackgroundTask(BackgroundTasks $BackgroundTasks, array $backgroundtask_keys): void
    {
        if ($this->option('order')) {
            return;
        }

        foreach ($backgroundtask_keys as $backgroundtask_key) {
            $BackgroundTasks->put($backgroundtask_key, 1);
        }
    }

    private function notifyUserSuccess(BackgroundTasks $BackgroundTasks, array $backgroundtask_keys, array $woocommerce_states): void
    {
        if ($this->option('order')) {
            return;
        }

        foreach ($backgroundtask_keys as $backgroundtask_key) {
            $BackgroundTasks->forget($backgroundtask_key);
        }
        $this->user->notify(FlashMessage::success('Die WooCommerce Bestellungen im Status <b>' . implode(', ', $woocommerce_states) . '</b> wurden importiert.', [
            'background_tasks' => App::make(BackgroundTasks::class)->all(),
        ]));
    }

    private function getOrderSourceIdsForStates(array $woocommerce_states): Collection
    {
        $states = collect($woocommerce_states)->map(function ($state) {
            return Status::fromWooCommerceSlug($state)->value;
        });

        return Order::where('user_id', $this->user->id)
            ->where('source_slug', ExternalType::WOOCOMMERCE->value)
            ->whereIn('state', $states)
            ->get()
            ->pluck('source_id');
    }

    private function importNotImportedOrders(Collection $not_imported_orders)
    {
        if ($not_imported_orders->isEmpty()) {
            return;
        }

        $WooCommerce = new \App\APIs\WooCommerce\WooCommerceOrder();

        foreach ($not_imported_orders as $woocommerce_order_id) {
            $response = $WooCommerce->order($woocommerce_order_id);

            if (!$response->successful()) {
                continue;
            }

            $order = WooCommerceOrderImporter::import($this->user->id, $response->json());
        }
    }

    private function getWooCommerceOrders(): array
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerceOrder();

        if ($this->option('order')) {
            $response = $WooCommerce->order($this->option('order'));
            if ($response->successful()) {
                return [
                    $response->json()
                ];
            }
            return [];
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
