<?php

namespace App\Console\Commands\Article\Imports;

use App\User;
use Illuminate\Console\Command;
use App\Importers\Orders\WooCommerceOrderImporter;

class WooCommerceCommand extends Command
{
    protected $signature = 'article:imports:woocommerce {user} {--order=}';

    protected $description = 'Import Articles from WooCommerce API';

    public function handle()
    {
        $user = User::findOrFail($this->argument('user'));

        $orders = $this->getOrders();

        foreach ($orders['data'] as $order) {
            WooCommerceOrderImporter::import($user->id, $order);
        }

        return self::SUCCESS;
    }

    private function getOrders(): array
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommercePurchase();

        if ($this->option('order')) {
            $response = $WooCommerce->order($this->option('order'));
            return [
                'data' => [
                    $response['data'],
                ],
            ];
        }

        $orders = $WooCommerce->orders();

        return $orders;
    }
}
