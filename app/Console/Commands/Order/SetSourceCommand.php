<?php

namespace App\Console\Commands\Order;

use Mockery\Matcher\Type;
use App\Models\Orders\Order;
use Illuminate\Console\Command;
use App\Enums\ExternalIds\ExernalType;
use Illuminate\Support\Facades\DB;
use App\Models\Users\CardmarketUser;

class SetSourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:set-source';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the source of the order.';

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
        $this->line('disabeling foreign key checks...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->line('Updating orders...');
        $this->updateOrders();

        $this->line('Updating cardmarket users...');
        $this->updateCardmarketUsers();

        $this->line('enableing foreign key checks...');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Sets the source of the existing orders.
     * Updates article_order.order_id, evaluations.order_id, transactions.order_id and images.imageable_id to the new ID of the order.
     * Changes the orders.id column to auto increment.
     *
     * @return void
     */
    private function updateOrders(): void
    {
        foreach (Order::orderBy('cardmarket_order_id', 'ASC')->cursor() as $key => $order) {
            $id = $key + 1;
            $this->output->write($id . "\t\t" . $order->cardmarket_oder_id . "\t\t");

            $updated = $order->update([
                'id' => $id,
                'source_slug' => ExernalType::CARDMARKET->value,
                'source_id' => $order->cardmarket_order_id,
            ]);

            if (! $updated) {
                $this->output->writeln('FAIL');
                break;
            }

            $affected_rows = DB::update('UPDATE article_order SET order_id = :order_id WHERE order_id = :cardmarket_order_id', [
                'order_id' => $id,
                'cardmarket_order_id' => $order->cardmarket_order_id,
            ]);
            $this->output->write($affected_rows . "\t\t");

            $affected_rows = DB::update('UPDATE evaluations SET order_id = :order_id WHERE order_id = :cardmarket_order_id', [
                'order_id' => $id,
                'cardmarket_order_id' => $order->cardmarket_order_id,
            ]);
            $this->output->write($affected_rows . "\t\t");

            $affected_rows = DB::update('UPDATE transactions SET order_id = :order_id WHERE order_id = :cardmarket_order_id', [
                'order_id' => $id,
                'cardmarket_order_id' => $order->cardmarket_order_id,
            ]);
            $this->output->write($affected_rows . "\t\t");

            $affected_rows = DB::update('UPDATE images SET imageable_id = :order_id WHERE imageable_id = :cardmarket_order_id', [
                'order_id' => $id,
                'cardmarket_order_id' => $order->cardmarket_order_id,
            ]);
            $this->output->writeln($affected_rows);
        }

        $this->line('Changing orders.id column to auto increment...');
        DB::statement('ALTER TABLE `orders` CHANGE `id` `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    /**
     * Sets the Source of the cardmarket_users table.
     * Updates orders.buyer_id and orders.seller_id to the new ID.
     * Changes the cardmarket_users.id column to auto increment.
     *
     * @return void
     */
    private function updateCardmarketUsers(): void
    {
        foreach (CardmarketUser::orderBy('cardmarket_user_id', 'ASC')->cursor() as $key => $cardmarket_user) {
            $id = $key + 1;
            $this->output->write($id . "\t\t" . $cardmarket_user->cardmarket_user_id . "\t\t");

            $updated = $cardmarket_user->update([
                'id' => $id,
                'source_slug' => ExernalType::CARDMARKET->value,
                'source_id' => $cardmarket_user->cardmarket_user_id,
            ]);

            if (! $updated) {
                $this->output->writeln('FAIL');
                break;
            }

            $affected_rows = DB::update('UPDATE orders SET buyer_id = :buyer_id WHERE buyer_id = :cardmarket_user_id', [
                'buyer_id' => $id,
                'cardmarket_user_id' => $cardmarket_user->cardmarket_user_id,
            ]);
            $this->output->write($affected_rows . "\t\t");

            $affected_rows = DB::update('UPDATE orders SET seller_id = :seller_id WHERE seller_id = :cardmarket_user_id', [
                'seller_id' => $id,
                'cardmarket_user_id' => $cardmarket_user->cardmarket_user_id,
            ]);
            $this->output->writeln($affected_rows);
        }

        $this->line('Changing cardmarket_users.id column to auto increment...');
        DB::statement('ALTER TABLE `cardmarket_users` CHANGE `id` `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');
    }
}
