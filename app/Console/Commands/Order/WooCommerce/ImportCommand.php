<?php

namespace App\Console\Commands\Order\WooCommerce;

use App\User;
use App\Models\Orders\Order;
use Illuminate\Console\Command;
use App\Importers\Orders\WooCommerceOrderImporter;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:woocommerce:import {user} {order}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an Order from a WooCommerce Order';

    protected User $user;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->user = User::find($this->argument('user'));
        $woocommerce_order_id = $this->argument('order');
        $woocommerce_order_response = (new \App\APIs\WooCommerce\WooCommerce())->order($woocommerce_order_id);

        WooCommerceOrderImporter::import($this->user->id, $woocommerce_order_response['data']);
    }
}
