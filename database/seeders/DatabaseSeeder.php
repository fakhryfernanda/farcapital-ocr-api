<?php

namespace Database\Seeders;

use App\Models\Identity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Seeds\CitiesSeeder;
use Laravolt\Indonesia\Seeds\VillagesSeeder;
use Laravolt\Indonesia\Seeds\DistrictsSeeder;
use Laravolt\Indonesia\Seeds\ProvincesSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RoleSeeder::class);
        $this->call([
            ProvincesSeeder::class,
            CitiesSeeder::class,
            DistrictsSeeder::class,
            VillagesSeeder::class,
        ]);

        User::query()->create([
            "email" => "admin@gmail.com",
            "password" => "admin",
            "id_role" => "1",
            "valid" => "1"
        ]);
    }
}
