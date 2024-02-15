<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => $this->faker->randomElement(["SmartPhone", "Baju", "Celana", 'Sepatu', 'Sendal']),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'description' => $this->faker->text,
            'image' => $this->faker->imageUrl,
            'stock' => $this->faker->numberBetween(1, 10),
            'user_id' => $this->faker->numberBetween(1, 2),
        ];
    }
}
