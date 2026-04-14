<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ObservationTest extends TestCase
{
    use RefreshDatabase;

    protected $observer;
    protected $station;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create necessary roles.
        $observerRole = Role::create(['name' => 'observer']);
        Role::create(['name' => 'admin']);

        // 2. Create a user with teh observer role.
        $this->observer = User::factory()->create([
            'role_id' => $observerRole->id,
        ]);

        // 3. Create a station and assign it to the observer.
        $this->station = Station::create([
            'name' => 'Estación Central',
            'location' => 'Madrid',
            'altitude' => 667,
        ]);

        $this->observer->stations()->attach($this->station->id);
    }

    /**
     * Test: An observer can add a valid observation.
     */

    public function test_observer_can_store_observation(): void
    {
        $payload = [
            'station_id' => $this->station->id,
            'temperature' => 25.5,
            'humidity' => 50,
            'pressure' => 1013,
            'wind_direction' => 180,
            'wind_speed' => 10,
            'precipitation' => 0,
            'observed_at' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Observation created successfully.');

        $this->assertDatabaseHas('observations', [
            'temperature' => 25.5,
            'station_id' => $this->station->id,
        ]);
    }

    /**
     * Test: The system generates and alert automatically if the temperature exceeds threshold.
     */
    public function test_system_generates_alert_on_extreme_temperature(): void
    {
        $payload = [
            'station_id' => $this->station->id,
            'temperature' => 46.0,
            'humidity' => 40,
            'pressure' => 1100,
            'wind_direction' => 180,
            'wind_speed' => 10,
            'precipitation' => 0,
            'observed_at' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $response->assertStatus(201);

        // Verify that an alert was created
        $this->assertDatabaseHas('alerts', [
            'title' => 'Calor extremo',
            'level' => 'red',
            'station_id' => $this->station->id,
        ]);
    }

    /**
     * Test: An observer cannot add an observation to a station they are not assigned to.
     */
    public function test_observer_cannot_post_to_unassigned_station(): void
    {
        $unassignedStation = Station::create([
            'name' => 'Estación Norte',
            'location' => 'Barcelona',
            'altitude' => 12,
        ]);

        $payload = [
            'station_id' => $unassignedStation->id,
            'temperature' => 20.0,
            'humidity' => 50,
            'pressure' => 1000,
            'wind_direction' => 90,
            'wind_speed' => 5,
            'precipitation' => 0,
            'observed_at' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $response->assertStatus(403);
    }
}
