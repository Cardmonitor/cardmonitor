<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use App\Models\Orders\Order;
use Faker\Generator as Faker;
use App\Enums\ExternalIds\ExternalType;
use App\Models\Users\CardmarketUser;

$factory->define(Order::class, function (Faker $faker) {

    $cardmarket_order_id = $faker->unique()->randomNumber();

    return [
        'user_id' => factory(User::class),
        'source_slug' => ExternalType::CARDMARKET->value,
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
