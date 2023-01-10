<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identity', function (Blueprint $table) {
            $table->id();
            $table->biginteger('id_user')->nullable();
            $table->string("nik", 16);
            $table->string("nama", 255);
            $table->string("tempat_lahir", 100);
            $table->date("tanggal_lahir");
            $table->string("jenis_kelamin", 1);
            $table->text("alamat");
            $table->string("rt", 3);
            $table->string("rw", 3);
            $table->string("kelurahan", 50);
            $table->string("kecamatan", 50);
            $table->string("kota", 50);
            $table->string("provinsi", 50);
            $table->string("agama", 20);
            $table->string("status_perkawinan", 20);
            $table->string("pekerjaan", 100);
            $table->string("kewarganegaraan", 50);
            $table->string("golongan_darah", 2)->nullable();
            $table->text("ktp");
            $table->text("foto")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('identity');
    }
}
