<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use Faker\Generator as Faker;

$factory->define(Card::class, function (Faker $faker) {
    return [
        'expansion_id' => factory(Expansion::class),
        'cardmarket_product_id' => $faker->unique()->randomNumber,
        'category_name' => 'Magic Single',
        'reprints_count' => $faker->numberBetween(1, 10),
        'name' => $faker->word,
        'website' => '',
        'image' => '',
        'number' => $faker->word,
        'rarity' => $faker->randomElement(['Common', 'Uncommon', 'Rare', 'Mystic']),
    ];
});
