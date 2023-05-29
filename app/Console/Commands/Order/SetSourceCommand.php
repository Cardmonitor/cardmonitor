<?php

namespace App\Console\Commands\Order;

use App\Models\Orders\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        foreach (Order::orderBy('cardmarket_order_id', 'ASC')->cursor() as $key => $order) {
            $id = $key + 1;
            $this->output->write($id . "\t\t" . $order->id . "\t\t");

            $updated = $order->update([
                'id' => $id,
                'source_slug' => 'cardmarket',
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

        $this->line('Changing id column to auto increment...');
        DB::statement('ALTER TABLE `orders` CHANGE `id` `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');

        $this->line('enableing foreign key checks...');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
