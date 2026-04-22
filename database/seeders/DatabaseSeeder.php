<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Observation;
use App\Models\Report;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed roles first
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('name', 'admin')->first();
        $observerRole = Role::where('name', 'observer')->first();
        $userRole = Role::where('name', 'user')->first();

        // 2. Create predefined users
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@meteo.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $observer = User::factory()->create([
            'name' => 'Observer',
            'email' => 'observer@meteo.com',
            'password' => Hash::make('password'),
            'role_id' => $observerRole->id,
        ]);

        $user = User::factory()->create([
            'name' => 'User',
            'email' => 'user@meteo.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);

        // 3. Create random users with random roles
        User::factory(50)->create([
            'password' => Hash::make('password'), // Ensure a known password for testing
        ]);

        // 4. Create 10 random stations
        $stations = Station::factory(10)->create();
        $stationsIds = $stations->pluck('id');

        // This guarantees that the observer user is assigned to at least 2 stations, so they can see some alerts.
        $observer->stations()->attach(
            $stations->random(min(2, $stations->count()))->pluck('id')->toArray()
        );

        // 5. Assign users (admins and observers) to stations.
        // We'll make 3 predefined users and 20 random users potential admins/observers.
        $potentialObservers = User::whereIn('role_id', [$adminRole->id, $observerRole->id])
            ->orWhereIn('id', [$admin->id, $observer->id])
            ->inRandomOrder()
            ->limit(20)
            ->get();

        $stations->each(function (Station $station) use ($potentialObservers) {
            // Assign between 1 and 5 observers/admins to each station
            $station->users()->syncWithoutDetaching(
                $potentialObservers->random(rand(1, min(5, $potentialObservers->count())))->pluck('id')->toArray()
            );
        });

        // 6. Create 50 random observations
        $observations = Observation::factory(50)->create();

        // 7. Generar alertas automáticamente a partir de las observaciones
        foreach ($observations as $observation) {
            $alerts = [];

            if ($observation->temperature >= 42) {
                $alerts[] = ['title' => 'Calor extremo', 'message' => "Temperatura extremadamente alta: {$observation->temperature}°C.", 'level' => 'red'];
            } elseif ($observation->temperature >= 35) {
                $alerts[] = ['title' => 'Ola de calor', 'message' => "Temperatura muy alta: {$observation->temperature}°C.", 'level' => 'orange'];
            } elseif ($observation->temperature <= -10) {
                $alerts[] = ['title' => 'Frío extremo', 'message' => "Temperatura extremadamente baja: {$observation->temperature}°C.", 'level' => 'red'];
            } elseif ($observation->temperature <= 0) {
                $alerts[] = ['title' => 'Helada', 'message' => "Temperatura bajo cero: {$observation->temperature}°C.", 'level' => 'orange'];
            }

            if ($observation->wind_speed >= 90) {
                $alerts[] = ['title' => 'Viento huracanado', 'message' => "Viento extremo: {$observation->wind_speed} km/h.", 'level' => 'red'];
            } elseif ($observation->wind_speed >= 60) {
                $alerts[] = ['title' => 'Viento fuerte', 'message' => "Viento fuerte: {$observation->wind_speed} km/h.", 'level' => 'orange'];
            } elseif ($observation->wind_speed >= 40) {
                $alerts[] = ['title' => 'Viento moderado', 'message' => "Viento moderado: {$observation->wind_speed} km/h.", 'level' => 'yellow'];
            }

            if ($observation->precipitation >= 50) {
                $alerts[] = ['title' => 'Lluvia torrencial', 'message' => "Precipitación muy intensa: {$observation->precipitation} mm.", 'level' => 'red'];
            } elseif ($observation->precipitation >= 20) {
                $alerts[] = ['title' => 'Lluvia intensa', 'message' => "Precipitación intensa: {$observation->precipitation} mm.", 'level' => 'orange'];
            }

            if ($observation->pressure <= 970) {
                $alerts[] = ['title' => 'Presión muy baja', 'message' => "Presión atmosférica muy baja: {$observation->pressure} hPa.", 'level' => 'orange'];
            } elseif ($observation->pressure >= 1040) {
                $alerts[] = ['title' => 'Presión muy alta', 'message' => "Presión atmosférica muy alta: {$observation->pressure} hPa.", 'level' => 'yellow'];
            }

            if ($observation->humidity >= 95) {
                $alerts[] = ['title' => 'Humedad extrema', 'message' => "Humedad muy alta: {$observation->humidity}%.", 'level' => 'yellow'];
            }

            foreach ($alerts as $alertData) {
                Alert::create(array_merge($alertData, [
                    'station_id' => $observation->station_id,
                    'observation_id' => $observation->id,
                    'is_active' => true,
                    'created_at' => $observation->observed_at,
                ]));
            }
        }

        // 8. Create 100 random reports
        Report::factory(10)->create([
            'station_id' => fn() => $stationsIds->random(),
            'user_id' => fn() => User::WhereIn('role_id', [$adminRole->id, $observerRole->id])->inRandomOrder()->value('id'),
        ]);
    }
}
