<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a start date within the last year
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now', 'UTC');

        // Generate an end date that is after the start date (by 1 and 3 months)
        $endDate = $this->faker->dateTimeBetween($startDate, (clone $startDate)->modify('+3 months'));

        $reportId = $this->faker->unique()->randomNumber(5);
        $fileType = $this->faker->randomElement(['pdf', 'docx', 'xlsx']);

        return [
            // Foreign keys: Ensure that the report is linked to existing station and user
            'station_id' => Station::factory(),
            'user_id' => User::factory(),

            'start_at' => $startDate,
            'end_at' => $endDate,

            'file_route' => "reports/report_{$reportId}." . $fileType,

            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now', 'UTC'),
            'updated_at' => $this->faker->optional()->dateTimeBetween($startDate, 'now', 'UTC'),
        ];
    }
}
