<?php

namespace App\Console\Commands\Order;

use App\Models\Orders\Order;
use App\User;
use Illuminate\Console\Command;

class GetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:get {user} {order} {--sync} {--json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected User $user;

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
        $this->user = User::find($this->argument('user'));
        $cardmarketOrder = $this->user->cardmarketApi->order->get($this->argument('order'));

        if ($this->option('json')) {
            $this->line(json_encode($cardmarketOrder));
        }
        else {
            dump($cardmarketOrder);
        }

        if ($this->option('sync')) {
            Order::updateOrCreateFromCardmarket($this->user->id, $cardmarketOrder['order'], Order::FORCE_UPDATE_OR_CREATE);
            $this->line('updated');
        }
    }
}
