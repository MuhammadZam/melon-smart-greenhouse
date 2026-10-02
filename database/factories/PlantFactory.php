<?php

namespace Database\Factories;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plant>
 */
class PlantFactory extends Factory
{
    protected $model = Plant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => 'Azolla Microphylla Kolam ' . fake()->unique()->randomElement(['A', 'B', 'C', 'D', 'E']),
            'location'    => 'Greenhouse ' . fake()->numberBetween(1, 3) . ', Rak ' . fake()->numberBetween(1, 4),
            'description' => 'Kolam budidaya Azolla uji coba pengamatan mikroklimat.',
            'status'      => 'active',
        ];
    }
}
