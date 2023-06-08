<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Orders\Order;
use App\Models\Users\CardmarketUser;
use App\User;
use Faker\Generator as Faker;

$factory->define(Order::class, function (Faker $faker) {

    $cardmarket_order_id = $faker->unique()->randomNumber();

    return [
        'user_id' => factory(User::class),
        'source_slug' => 'cardmarket',
        'source_id' => $cardmarket_order_id,
        'buyer_id' => factory(CardmarketUser::class),
        'seller_id' => factory(CardmarketUser::class),
        'shipping_method_id' => 1,
        'cardmarket_order_id' => $cardmarket_order_id,
        'state' => 'paid',
        'shippingmethod' => 'Standardbrief',
        'shipping_name' => $faker->name,
        'shipping_extra' => '',
        'shipping_street' => $faker->streetName . ' ' . $faker->buildingNumber,
        'shipping_zip' => $faker->postcode,
        'shipping_city' => $faker->city,
        'shipping_country' => 'D',
        'articles_count' => $faker->numberBetween(1, 123),
    ];
});
