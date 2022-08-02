<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shipping_method_id');

            $table->unsignedBigInteger('cardmarket_order_id');
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('seller_id')->nullable();

            $table->string('state');
            $table->dateTime('bought_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->string('canceled_reason')->nullable();
            $table->string('shippingmethod');

            $table->string('shipping_name');
            $table->string('shipping_extra')->nullable();
            $table->string('shipping_street');
            $table->string('shipping_zip');
            $table->string('shipping_city');
            $table->string('shipping_country');
            $table->string('tracking_number')->nullable();

            $table->decimal('provision', 15, 6)->default(0);

            $table->decimal('items_cost', 15, 6)->default(0);

            $table->decimal('articles_revenue', 15, 6)->default(0);
            $table->decimal('articles_cost', 15, 6)->default(0);
            $table->decimal('articles_profit', 15, 6)->default(0);

            $table->decimal('shipment_revenue', 15, 6)->default(0);
            $table->decimal('shipment_cost', 15, 6)->default(0);
            $table->decimal('shipment_profit', 15, 6)->default(0);

            $table->decimal('revenue', 15, 6)->default(0);
            $table->decimal('cost', 15, 6)->default(0);
            $table->decimal('profit', 15, 6)->default(0);

            $table->unsignedInteger('articles_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('buyer_id')->references('id')->on('cardmarket_users');
            $table->foreign('seller_id')->references('id')->on('cardmarket_users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
