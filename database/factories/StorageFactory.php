<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Storages\Storage;
use App\User;
use Faker\Generator as Faker;

$factory->define(Storage::class, function (Faker $faker) {
    return [
        'user_id' => factory(User::class),
        'parent_id' => null,
        'number' => null,
        'name' => $faker->word,
        'slots' => $faker->randomNumber(),
    ];
});
