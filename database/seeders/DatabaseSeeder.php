<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Observation;
use App\Models\Report;
use App\Models\Station;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed roles first
        $this->call(RoleSeeder::class);

        // 2. Then, create users
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role_id' => 1, // Assigning 'admin' role
        ]);

        User::factory()->create([
            'name' => 'Observer',
            'email' => 'observer@example.com',
            'role_id' => 2, // Assigning 'observer' role
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@example.com',
            'role_id' => 3, // Assigning 'user' role
        ]);

        // 3. Create 10 random stations
        Station::factory(10)->create();

        // 4. Create 500 random observations
        Observation::factory(500)->create();

        // 5. Create 50 random alerts
        Alert::factory(50)->create();

        // 6. Create 100 random reports
        Report::factory(100)->create();
    }
}
