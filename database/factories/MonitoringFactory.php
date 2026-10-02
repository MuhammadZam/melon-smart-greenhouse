<?php

namespace Database\Factories;

use App\Models\Monitoring;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Monitoring>
 */
class MonitoringFactory extends Factory
{
    protected $model = Monitoring::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plant_id'        => Plant::factory(),
            'temperature'     => fake()->randomFloat(2, 23.0, 31.5),
            'ph'              => fake()->randomFloat(2, 6.2, 7.6),
            'humidity'        => fake()->randomFloat(2, 65.0, 85.0),
            'plant_height'    => fake()->randomFloat(2, 1.2, 4.0),
            'plant_density'   => fake()->randomFloat(2, 55.0, 92.0),
            'plant_condition' => fake()->randomElement(['Baik', 'Subur', 'Optimal', 'Perlu Perhatian']),
            'plant_color'     => fake()->randomElement(['Hijau Segar', 'Hijau Pekat', 'Hijau Muda']),
            'notes'           => 'Data observasi simulasi pengujian monitoring.',
            'monitored_at'    => fake()->dateTimeBetween('-10 days', 'now'),
        ];
    }
}
