<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\ListingSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'TestClient',
            'email' => 'tc@t.fr',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'TestHost',
            'email' => 'th@t.fr',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'TestCoHost',
            'email' => 'tch@t.fr',
            'password' => bcrypt('password'),
        ]);

        $this->call(ListingSeeder::class);
    }
}
