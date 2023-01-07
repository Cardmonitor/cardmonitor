<?php

namespace Database\Factories\Articles;

use Illuminate\Database\Eloquent\Factories\Factory;

class StoringHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => factory(\App\User::class)->create()->id,
        ];
    }
}
