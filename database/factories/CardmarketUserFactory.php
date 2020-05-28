<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Users\CardmarketUser;
use Faker\Generator as Faker;

$factory->define(CardmarketUser::class, function (Faker $faker) {
    return [
        'cardmarket_user_id' => $faker->unique()->randomNumber,
        'username' => $faker->userName,
        'registered_at' => $faker->dateTime,
        'is_commercial' => $faker->boolean,
        'is_seller' => $faker->boolean,
        'firstname' => $faker->firstName,
        'name' => $faker->name,
        'extra' => '',
        'street' => $faker->streetName . ' ' . $faker->buildingNumber,
        'zip' => $faker->postcode,
        'city' => $faker->city,
        'country' => 'D',
        'phone' => '',
        'email' => '',
        'vat' => '',
        'legalinformation' => '',
        'risk_group' => 1,
        'loss_percentage' => '0 - 2%',
        'unsent_shipments' => 0,
        'reputation' => 0,
        'ships_fast' => 0,
        'sell_count' => 2,
        'sold_items' => 4,
        'avg_shipping_time' => 1,
        'is_on_vacation' => false,
    ];
});
