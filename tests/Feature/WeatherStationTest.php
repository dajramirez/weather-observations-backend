<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Observation;
use App\Models\Role;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WeatherStationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $observer;
    private Station $station;

    /**
     * Set up initial environment for testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create basic roles
        $adminRole = Role::create(['name' => 'admin']);
        $observerRole = Role::create(['name' => 'observer']);
        Role::create(['name' => 'user']);

        // Create users
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->observer = User::factory()->create(['role_id' => $observerRole->id]);

        //Create a station and assign it to the observer.
        $this->station = Station::create([
            'name' => 'Test Station',
            'location' => 'Test Location',
            'altitude' => 100,
        ]);

        $this->observer->stations()->attach($this->station->id);
    }
    
        /*
        |-------------------------------------------------------------------------
        | UNIT TESTS (Logic and Validation)
        |-------------------------------------------------------------------------
        */

    /**
     * Test 1: High Temperature triggers Red Alert (Extreme Heat)
     */
    public function test_high_temperature_triggers_red_alert(): void
    {
        $payload = [
            'station_id' => $this->station->id,
            'temperature' => 43, // Threshold > 42°C
            'humidity' => 20,
            'pressure' => 1015,
            'wind_direction' => 0,
            'wind_speed' => 10,
            'precipitation' => 0,
            'observed_at' => now()->toDayDateTimeString(),
        ];

        $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $this->assertDatabaseHas('alerts', [
            'title' => 'Extreme Heat Risk',
            'level' => 'red',
            'station_id' => $this->station->id,
        ]);
    }

    /**
     * Test 2: Freezing Temperature triggers Orange Alert (Freezing Warning)
     */
    public function test_freezing_temperature_triggers_orange_alert(): void
    {
        $payload = [
            'station_id' => $this->station->id,
            'temperature' => -5, // Threshold < 0°C
            'humidity' => 30,
            'pressure' => 1015,
            'wind_direction' => 0,
            'wind_speed' => 10,
            'precipitation' => 0,
            'observed_at' => now()->toDayDateTimeString(),
        ];

        $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $this->assertDatabaseHas('alerts', [
            'title' => 'Freezing Warning',
            'level' => 'orange',
        ]);
    }

    /**
     * Test 3: Normal temperature does not trigger any alert.
     */
    public function test_normal_temperature_does_not_trigger_alert(): void
    {
        $payload = [
            'station_id' => $this->station->id,
            'temperature' => 20, // Normal temperature
            'humidity' => 50,
            'pressure' => 1015,
            'wind_direction' => 0,
            'wind_speed' => 10,
            'precipitation' => 0,
            'observed_at' => now()->toDayDateTimeString(),
        ];

        $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $this->assertEquals(0, Alert::count());
    }

    /**
     * Test 4: Observer cannot post to a station they don't manage.
     */
    public function test_observer_cannot_post_to_unassigned_station(): void
    {
        $otherStation = Station::create(['name' => 'Other Station', 'location' => 'Other Location', 'altitude' => 200]);

        $payload = [
            'station_id' => $otherStation->id,
            'temperature' => 20,
            'humidity' => 50,
            'pressure' => 1015,
            'wind_direction' => 0,
            'wind_speed' => 10,
            'precipitation' => 0,
            'observed_at' => now()->toDayDateTimeString(),
        ];

        $response = $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test 5: Observation requires numeric temperature.
     */
    public function test_observation_requires_numeric_temperature(): void
    {
        $payload = [
            'station_id' => $this->station->id,
            'temperature' => 'Hot',
            'humidity' => 50,
            'pressure' => 1015,
            'wind_direction' => 0,
            'wind_speed' => 10,
            'precipitation' => 0,
            'observed_at' => now()->toDayDateTimeString(),
        ];

        $response = $this->actingAs($this->observer)
            ->postJson('/api/observer/observations', $payload);

        $response->assertJsonValidationErrors(['temperature']);
    }

    /**
     * Test 6: Admin can toggle alert status.
     */
    public function test_admin_can_toggle_alert_status(): void
    {
        $alert = Alert::create([
            'station_id' => $this->station->id,
            'title' => 'Test Alert',
            'message' => 'This is a test alert.',
            'level' => 'yellow',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/alerts/{$alert->id}/toggle-active");

        $response->assertStatus(200);
        $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'is_active' => false]);
    }

    /**
     * Test 7: Non-admin cannot toggle alert status.
     */
    public function test_non_admin_cannot_toggle_alert_status(): void
    {
        $alert = Alert::create([
            'station_id' => $this->station->id,
            'title' => 'Test Alert',
            'message' => 'This is a test alert.',
            'level' => 'yellow',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->observer)
            ->patchJson("/api/admin/alerts/{$alert->id}/toggle-active");

        $response->assertStatus(403);
    }

    /**
     * Test 8: Station listing requires authentication.
     */
    public function test_station_listing_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/stations');
        $response->assertStatus(401);
    }

    /**
     * Test 9: Observation model calculates humidity correctly (Business Logic check).
     */
    public function test_observation_stores_data_correctly(): void
    {
        $obs = Observation::create([
            'station_id' => $this->station->id,
            'user_id' => $this->observer->id,
            'temperature' => 25,
            'humidity' => 60,
            'pressure' => 1013,
            'wind_direction' => 90,
            'wind_speed' => 15,
            'precipitation' => 5,
            'observed_at' => now(),
        ]);

        $this->assertEquals(60, $obs->humidity);
    }

    /**
     * Test 10: Role check helper method.
     */
    public function test_user_has_role_method(): void
    {
        $this->assertTrue($this->admin->hasRole('admin'));
        $this->assertFalse($this->admin->hasRole('observer'));
    }

    /*
    |--------------------------------------------------------------------------
    | INTEGRATION TESTS
    |--------------------------------------------------------------------------
    */

    /**
     * Integration Test: Complete workflow of an emergency event.
     * 1. Observer posts a critical observation.
     * 2. System automatically generates an alert.
     * 3. Admin views the alert and deactivates it after resolving the issue.
     */
    public function test_critical_weather_event_workflow(): void
    {
        // 1. Observer sends extreme temperature observation
        $this->actingAs($this->observer)->postJson('/api/observer/observations', [
            'station_id' => $this->station->id,
            'temperature' => 45.5, // Extreme heat
            'humidity' => 15,
            'pressure' => 1005,
            'wind_direction' => 180,
            'wind_speed' => 25,
            'precipitation' => 0,
            'observed_at' => now()->toDayDateTimeString(),
        ]);

        // 2. Verify alert exists in system
        $alert = Alert::where('title', 'Extreme Heat Risk')->first();
        $this->assertNotNull($alert);
        $this->assertTrue($alert->is_active);

        // 3. Admin logs in, checks alert list, and deactivates the alert.
        $this->actingAs($this->admin);

        $listResponse = $this->getJson('/api/admin/alerts');
        $listResponse->assertStatus(200)->assertJsonFragment(['title' => 'Extreme Heat Risk']);

        $toggleResponse = $this->patchJson("/api/admin/alerts/{$alert->id}/toggle-active");
        $toggleResponse->assertStatus(200);

        // Final verification
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'is_active' => false,
        ]);
    }
}
