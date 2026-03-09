<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Observation;
use App\Models\Report;
use App\Models\Station;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        // Define roles for reference
        $adminRole = Role::where('name', 'admin')->first();
        $observerRole = Role::where('name', 'observer')->first();
        $userRole = Role::where('name', 'user')->first();

        // 2. Create predefined users
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@meteo.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id, // Assign the 'admin' id
        ]);

        $observer = User::factory()->create([
            'name' => 'Observer',
            'email' => 'observer@meteo.com',
            'password' => Hash::make('password'),
            'role_id' => $observerRole->id, // Assign the 'observer' id
        ]);

        $regularUser = User::factory()->create([
            'name' => 'User',
            'email' => 'user@meteo.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id, // Assign the 'user' id
        ]);

        // 3. Create random users with random roles
        User::factory(50)->create([
            'password' => Hash::make('password'), // Ensure a known password for testing
        ]);

        // 4. Create 10 random stations
        $stations = Station::factory(10)->create();
        $stationsIds = $stations->pluck('id');

        // 5. Assign users (admins and observers) to stations.
        // We'll make 3 predefined users and 20 random users potential admins/observers.
        $potentialObservers = User::whereIn('role_id', [$adminRole->id, $observerRole->id])
            ->orWhereIn('id', [$admin->id, $observer->id])
            ->inRandomOrder()
            ->limit(20)
            ->get();

        $stations->each(function (Station $station) use ($potentialObservers) {
            // Assign between 1 and 5 observers/admins to each station
            $station->users()->attach(
                $potentialObservers->random(rand(1, min(5, $potentialObservers->count())))->pluck('id')->toArray()
            );
        });

        // 6. Create 500 random observations
        $observations = Observation::factory(500)->create();
        $observationsIds = $observations->pluck('id');

        // 9. Create 50 random alerts
        Alert::factory(50)->create([
            'observation_id' => fn() => $observationsIds->random(),
            'station_id' => fn() => $stationsIds->random(),
        ]);

        // 10. Create 100 random reports
        Report::factory(100)->create([
            'station_id' => fn() => $stationsIds->random(),
            'user_id' => fn() => User::WhereIn('role_id', [$adminRole->id, $observerRole->id])->inRandomOrder()->value('id'),
        ]);
    }
}
