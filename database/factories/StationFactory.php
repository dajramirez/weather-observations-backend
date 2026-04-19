<?php

namespace Database\Factories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

class StationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Station::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->city . ' ' . $this->faker->randomElement(['Norte', 'Sur', 'Este', 'Oeste', 'Central', 'Montaña']);

        return [
            'name' => $name,
            'location' => $this->faker->address,
            // Altitude between 0 and 5000 meters (adjust if necessary)
            'altitude' => $this->faker->numberBetween(0, 5000),
            'description' => $this->faker->text(200),
        ];
    }
}
