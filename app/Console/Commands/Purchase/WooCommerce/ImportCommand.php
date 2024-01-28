<?php

namespace App\Console\Commands\Purchase\WooCommerce;

use App\User;
use Illuminate\Console\Command;
use App\Importers\Orders\WooCommercePurchaseImporter;

class ImportCommand extends Command
{
    protected $signature = 'purchase:woocommerce:import {user} {order}';

    protected $description = 'Creates a Purchase from a WooCommerce Order';

    protected User $user;

    public function handle()
    {
        $this->user = User::find($this->argument('user'));
        $woocommerce_order_id = $this->argument('order');
        $woocommerce_order_response = (new \App\APIs\WooCommerce\WooCommercePurchase())->order($woocommerce_order_id);

        WooCommercePurchaseImporter::import($this->user->id, $woocommerce_order_response->json());
    }
}
