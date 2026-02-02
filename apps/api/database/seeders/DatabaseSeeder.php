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
        User::firstOrCreate(
            ['email' => 'tc@t.fr'],
            ['name' => 'TestClient', 'password' => bcrypt('password')]
        );

        User::firstOrCreate(
            ['email' => 'th@t.fr'],
            ['name' => 'TestHost', 'password' => bcrypt('password')]
        );

        User::firstOrCreate(
            ['email' => 'tch@t.fr'],
            ['name' => 'TestCoHost', 'password' => bcrypt('password')]
        );

        $this->call(ListingSeeder::class);
    }
}
