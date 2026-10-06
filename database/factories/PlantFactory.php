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
            'name'        => 'Melon ' . ucfirst(fake()->word()) . ' Plot ' . fake()->unique()->numberBetween(100, 99999),
            'location'    => 'Greenhouse ' . fake()->numberBetween(1, 5) . ' - Sektor ' . fake()->randomElement(['Barat', 'Timur', 'Utara', 'Selatan']),
            'description' => 'Plot budidaya tanaman melon uji coba pengamatan mikroklimat.',
            'status'      => fake()->randomElement(['active', 'harvested', 'inactive']),
        ];
    }
}
