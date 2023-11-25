<?php

namespace Database\Factories\Articles;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalIdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'article_id' => factory(\App\Models\Articles\Article::class)->create()->id,
            'user_id' => factory(\App\User::class)->create()->id,
            'external_type' => $this->faker->word,
            'external_id' => $this->faker->uuid,
        ];
    }
}
