<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::query()->create(
            [
                'nama_role' => 'Admin'
            ]
        );
        Role::query()->create(
            [
                'nama_role' => 'User'
            ]
        );
    }
}
