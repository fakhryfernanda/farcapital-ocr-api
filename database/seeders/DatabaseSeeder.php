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
            "email" => "hilih@gmail.com",
            "password" => "hilih",
            "id_role" => "1",
            "valid" => "1"
        ]);

        User::query()->create([
            "email" => "endji@gmail.com",
            "password" => "endji",
            "id_role" => "2",
            "valid" => "1"
        ]);

        Identity::query()->create([
            "id_user" => "2",
            "nik" => "1234567812345678",
            "nama" => "Endjiansyah",
            "tempat_lahir" => "Jepara",
            "tanggal_lahir" => "2020-10-10",
            "jenis_kelamin" => "1",
            "alamat" => "Jalan gunung batu no 3",
            "rt" => "004",
            "rw" => "002",
            "kelurahan" => "Tegal Alur",
            "kecamatan" => "Gunung Batu",
            "kota" => "Jepara",
            "provinsi" => "Jawa Tengah",
            "agama" => "Islam",
            "status_perkawinan" => "Belum Kawin",
            "pekerjaan" => "Pelajar/Mahasiswa",
            "kewarganegaraan" => "WNI",
            "golongan_darah" => "B",
            "ktp" => "KTP.jpg",
            "foto" => "foto.jpg"
        ]);
    }
}
