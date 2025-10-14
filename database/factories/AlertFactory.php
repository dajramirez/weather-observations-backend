<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Define default state for Alert model
        $levels = ['red', 'orange', 'yellow', 'green'];
        $level = $this->faker->randomElement($levels);

        $titles = [
            'red' => 'Maximum Alert: Inminent Danger',
            'orange' => 'Serious Warning: High Caution',
            'yellow' => 'Important Notice: Risky Conditions',
            'green' => 'Operation Normal Conditions',
        ];

        // Define the probability of being active (80% chance of being active)
        $isActive = $this->faker->boolean(80);

        return [
            // Foreign key: Ensure that the alert is linked to an existing station
            'station_id' => Station::factory(),

            'title' => $titles[$level] ?? 'Weather Alert',
            'message' => $this->faker->sentence(15),
            'level' => $level,
            'is_active' => $isActive,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
