<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Articles\Article;
use App\Models\Cards\Card;
use App\Models\Localizations\Language;
use App\User;
use Faker\Generator as Faker;

$factory->define(Article::class, function (Faker $faker) {
    return [
        'card_id' => factory(Card::class),
        'user_id' => factory(User::class),
        'language_id' => Language::DEFAULT_ID,
        'cardmarket_article_id' => $faker->randomNumber,
        'cardmarket_last_edited' => $faker->dateTime,
        'condition' => $faker->randomElement(['M', 'NM', 'EX', 'GD']),
        'unit_cost' => $faker->randomFloat(6),
        'unit_price' => $faker->randomFloat(6),
        'provision' => $faker->randomFloat(6),
        'bought_at' => null,
        'exported_at' => null,
        'sold_at' => null,
        'hash' => null,
        'number' => null,
        'is_foil' => 0,
        'is_altered' => 0,
        'is_playset' => 0,
        'is_signed' => 0,
    ];
});
