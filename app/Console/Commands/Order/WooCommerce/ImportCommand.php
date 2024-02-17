<?php

namespace App\Console\Commands\Order\WooCommerce;

use App\User;
use Illuminate\Console\Command;
use App\Importers\Orders\WooCommerceOrderImporter;

class ImportCommand extends Command
{
    protected $signature = 'order:woocommerce:import {user} {--states=*} {--order=}';

    protected $description = 'Import Orders from WooCommerce API';

    public function handle()
    {
        $user = User::findOrFail($this->argument('user'));

        $orders = $this->getOrders();

        foreach ($orders as $order) {
            $order = WooCommerceOrderImporter::import($user->id, $order);
        }

        return self::SUCCESS;
    }

    private function getOrders(): array
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
