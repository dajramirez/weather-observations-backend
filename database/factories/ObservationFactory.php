<?php

namespace Database\Factories;

use App\Models\Observation;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Observation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random user and station ID from the database, assuming they have already been created (seeded).
        $stationId = Station::inRandomOrder()->first() ?? Station::factory();
        $userId = User::inRandomOrder()->first() ?? User::factory();

        // Generate an observation date within the last year
        $observedDate = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            // Foreign keys
            'station_id' => $stationId,
            'user_id' => $userId,

            // Meteo data
            'observed_at' => $observedDate,
            'temperature' => $this->faker->randomFloat(2, -10, 40), // Between -10 and 40 °C
            'humidity' => $this->faker->randomFloat(2, 20, 100),    // Between 20% and 100%
            'pressure' => $this->faker->randomFloat(2, 950, 1050), // Pressure in hPa
            'wind_direction' => $this->faker->numberBetween(0, 360), // Wind direction (0-360 degrees)
            'wind_speed' => $this->faker->randomFloat(2, 0, 50),       // Wind speed (km/h)
            'precipitation' => $this->faker->randomFloat(2, 0, 100), // Precipitation (mm)
        ];
    }
}
