<?php

namespace App\Console\Commands\Article\Imports;

use App\User;
use Illuminate\Console\Command;
use App\Models\Articles\Article;

class WooCommerceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:imports:woocommerce {user} {--order=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Articles from WooCommerce API';

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
        $user = User::findOrFail($this->argument('user'));

        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();
        $orders = $this->getOrders();

        foreach ($orders['data'] as $order) {
            Article::updateOrCreateFromWooCommerceAPIOrder($user->id, $order);
        }

        return self::SUCCESS;
    }

    private function getOrders(): array
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerce();

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
