<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        User::query()->create([
            "email" => "hilih@gmail.com",
            "password" => "hilih",
            "id_role" => "1",
            "remember_token" => Str::random(10)
        ]);
    }
}
