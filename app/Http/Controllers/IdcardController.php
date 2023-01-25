<?php

namespace App\Http\Controllers;


use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Models\Identity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;


class IdcardController extends Controller
{
    protected $ocr;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * 
     * 
     */


    public function readImage(Request $request)
    {

        //ambil image dari reques
        $image = $request->file('image');


        if (isset($image)) {

            //konversi oleh tesseract

            $tesseract = new TesseractOCR($image);
            $parsedText = ($tesseract)->dpi(72)->lang('ind')->run();

            //merubah jadi array
            $new_pattern = preg_split('/\n/', $parsedText);

            //menghapus array kosong dan reset index
            $new_pattern = array_values(array_filter($new_pattern));

            //apabila isi array kurang dari 13 maka konversi diulang dengan merubah gambar menjadi greyscale dan menambahkan kontras dan brightness
            if (count($new_pattern) <= 13) {

                $image = Image::make($image)->greyscale()->contrast(10)->brightness(20);
                //save gambar sementara
                $image->save('greyscale/bar.jpg');

                //konversi oleh tesseract
                $tesseract = new TesseractOCR('greyscale/bar.jpg');
                $parsedText = ($tesseract)->dpi(72)->run();

                //merubah jadi array
                $new_pattern = preg_split('/\n/', $parsedText);

                //menghapus array kosong dan reset index
                $new_pattern = array_values(array_filter($new_pattern));
                //hapus lagi photonya
                unlink('greyscale/bar.jpg');



                if (count($new_pattern) <= 13) {

                    $image = Image::make($image)->greyscale()->contrast(15)->brightness(20);
                    //save gambar sementara
                    $image->save('greyscale/bar.jpg');

                    //konversi oleh tesseract
                    $tesseract = new TesseractOCR('greyscale/bar.jpg');
                    $parsedText = ($tesseract)->dpi(72)->run();

                    //merubah jadi array
                    $new_pattern = preg_split('/\n/', $parsedText);

                    //menghapus array kosong dan reset index
                    $new_pattern = array_values(array_filter($new_pattern));
                    //hapus lagi photonya
                    unlink('greyscale/bar.jpg');


                    if (count($new_pattern) <= 13) {

                        $image = Image::make($image)->greyscale()->contrast(20)->brightness(20);
                        //save gambar sementara
                        $image->save('greyscale/bar.jpg');

                        //konversi oleh tesseract
                        $tesseract = new TesseractOCR('greyscale/bar.jpg');
                        $parsedText = ($tesseract)->dpi(72)->run();

                        //merubah jadi array
                        $new_pattern = preg_split('/\n/', $parsedText);

                        //menghapus array kosong dan reset index
                        $new_pattern = array_values(array_filter($new_pattern));
                        //hapus lagi photonya
                        unlink('greyscale/bar.jpg');
                        if (count($new_pattern) <= 13) {

                            $image = Image::make($image)->greyscale()->contrast(25)->brightness(20);
                            //save gambar sementara
                            $image->save('greyscale/bar.jpg');

                            //konversi oleh tesseract
                            $tesseract = new TesseractOCR('greyscale/bar.jpg');
                            $parsedText = ($tesseract)->dpi(72)->lang('ind')->userWords('user.txt')->run();

                            //merubah jadi array
                            $new_pattern = preg_split('/\n/', $parsedText);

                            //menghapus array kosong dan reset index
                            $new_pattern = array_values(array_filter($new_pattern));
                            //hapus lagi photonya
                            unlink('greyscale/bar.jpg');

                            if (count($new_pattern) <= 13) {

                                $image = Image::make($image)->greyscale()->contrast(30)->brightness(20);
                                //save gambar sementara
                                $image->save('greyscale/bar.jpg');

                                //konversi oleh tesseract
                                $tesseract = new TesseractOCR('greyscale/bar.jpg');
                                $parsedText = ($tesseract)->dpi(72)->lang('ind')->userWords('user.txt')->run();

                                //merubah jadi array
                                $new_pattern = preg_split('/\n/', $parsedText);

                                //menghapus array kosong dan reset index
                                $new_pattern = array_values(array_filter($new_pattern));
                                //hapus lagi photonya
                                unlink('greyscale/bar.jpg');

                                if (count($new_pattern) <= 13) {

                                    return response()->json([
                                        'status' => false,
                                        'message' => 'KTP Tidak Terdeteksi, Mohon Upload ulang',
                                        'data' => 'backscan'
                                    ]);
                                }
                            }
                        }
                    }
                }
            }


            //mencari array yang berisi provinsi atau setidaknya paling sama dengan kata provinsi
            $words1 = $new_pattern;
            usort($new_pattern, function ($a, $b) {
                similar_text('PROVINSI', $a, $percentA);
                similar_text('PROVINSI', $b, $percentB);
                return $percentB - $percentA;
            });

            //apabila sudah ditemukan maka array sebelumnya akan dipotong
            $cutter = array_search($new_pattern[0], $words1);
            $new_pattern = array_slice($words1, $cutter);



            // setelah dipotong. apabila tidak ditemukan array yang berisi provinsi maka buat response penolakan
            if (count($new_pattern) <= 13) {

                return response()->json([
                    'status' => false,
                    'message' => 'ktp tidak terdeteksi',
                    'data' => 'backscan'
                ]);
            } else {
                //mencari provinsi
                $provinsi_baru = $new_pattern[0];

                //explode array untuk mengganti kata provinsi yang tidak sempurna.

                $a = explode(" ", $provinsi_baru);
                $a[0] = 'PROVINSI';
                //provinsi digabung kembali
                $b = implode(" ", $a);
                $provinsi = $b;


                //membuat pola regex
                $pattern = "/(?<=provinsi ).*/i";
                //mencari string dengan pola diatas. apabila ada string berisi provinsi maka ambil string selanjutnya
                $isExisted = preg_match($pattern, $provinsi, $matches);
                //jika true
                if ($isExisted == 1) {
                    $provinsi = $matches[0];
                    //mereplace kata dan yang diambil hanya huruf dan spasi. selainnya dibuang
                    $provinsi = preg_replace("/[^a-zA-Z ]/", "", $provinsi);
                    //menghilangkan spasi di depan dan belakang kata
                    $provinsi = trim($provinsi);


                    //array untuk replace kata provinsi yang masih kurang tepat
                    $provinsi_ktp = [
                        'NANGGROE ACEH DARUSSALAM',
                        'SUMATERA UTARA',
                        'SUMATERA SELATAN',
                        'SUMATERA BARAT',
                        'BENGKULU',
                        'RIAU',
                        'KEPULAUAN RIAU',
                        'JAMBI',
                        'LAMPUNG',
                        'BANGKA BELITUNG',
                        'KALIMANTAN BARAT',
                        'KALIMANTAN TIMUR',
                        'KALIMANTAN SELATAN',
                        'KALIMANTAN TENGAH',
                        'KALIMANTAN UTARA',
                        'BANTEN',
                        'DKI JAKARTA',
                        'JAWA BARAT',
                        'JAWA TENGAH',
                        'DAERAH ISTIMEWA YOGYAKARTA',
                        'JAWA TIMUR',
                        'BALI',
                        'NUSA TENGGARA TIMUR',
                        'NUSA TENGGARA BARAT',
                        'GORONTALO',
                        'SULAWESI BARAT',
                        'SULAWESI TENGAH',
                        'SULAWESI UTARA',
                        'SULAWESI TENGGARA',
                        'SULAWESI SELATAN',
                        'MALUKU UTARA',
                        'MALUKU',
                        'PAPUA BARAT',
                        'PAPUA',
                        'PAPUA TENGAH',
                        'PAPUA PEGUNUNGAN'
                    ];

                    //mencari kata provinsi yang paling sama dengan data yang didapat dari scan tesseract 
                    usort($provinsi_ktp, function ($a, $b) use ($provinsi) {
                        similar_text($provinsi, $a, $percentA);
                        similar_text($provinsi, $b, $percentB);
                        return $percentB - $percentA;
                    });
                    $newprovinsi = $provinsi_ktp[0];
                    //jika persamaan ktp lebih dari 50% maka data ktp direplace dengan data yang didapat dari array
                    similar_text($provinsi, $newprovinsi, $percent);
                    if ($percent > 50) {
                        $provinsi = $newprovinsi;
                    }
                } else {
                    //jika tesseract belum bisa mendeteksi kata setelah provinsi maka variable provinsi dikosongkan
                    $provinsi = '';
                }

                // -----batas kota-------
                //array index 1 berisi kota
                $kota = $new_pattern[1];
                //mengambil string yang berisi huruf dan spasi saja
                $kota = preg_replace("/[^a-zA-Z ]/", "", $kota);

                //menghilangkan spasi di depan dan belakang string
                $kota = trim($kota);

                //ambil kode provinsi untuk menjadi parameter pencarian di table kota
                $data_provinsi = Province::where('name', 'LIKE', '%' . $provinsi . '%')->first();

                //apabila data provinsi kosong maka kata kota tidak di replace. jika tidak kosong maka lanjut ke proses selanjutnya
                if ($data_provinsi !== null) {
                    $result = $data_provinsi->code;
                    //mencari array kota yang sesuai dengan kode provinsi
                    $kota_ktp = City::where('province_code', $result)->get();


                    foreach ($kota_ktp as $kotaktp) {
                        $ktpkota[] = $kotaktp->name;
                    }

                    //mencari kata provinsi yang paling sama dengan data yang didapat dari scan tesseract 
                    usort($ktpkota, function ($a, $b) use ($kota) {
                        similar_text($kota, $a, $percentA);
                        similar_text($kota, $b, $percentB);
                        return $percentB - $percentA;
                    });

                    $newkota = $ktpkota[0];
                    //jika array ke 0 yang didapat di atas persamaanya lebih dari 50 persen maka kota di replace. jika tidak sama maka kata kota tetap memakai data yang didapat
                    similar_text($kota, $newkota, $percent);
                    if ($percent > 50) {
                        $kota = $newkota;
                    }
                }


                // -----batas NIK-------
                //nik berada di array index[2]
                $nik = $new_pattern[2];

                //membersihkan string yang berisi nik. dan diambil hanya huruf, spasi dan angka saja.
                $nik = preg_replace("/[^a-zA-Z0-9 ]/", "", $nik);

                //membersihkan spasi di depan dan belakang string
                $nik = trim($nik);

                //merubah string yang berisi nik menjadi array
                $nik = explode(" ", $nik);

                //array index ke 0 harus berisi kata NIK. dilakukan pengecekan dengan similar text. 
                //apabila array tersebut nilai similar lebih 50% maka array 0 kita replace menjadi NIK 
                //agar bisa dilakukan pengecekan berikutnya.
                similar_text("NIK", $nik[0], $percent);
                if ($percent > 30) {
                    $nik[0] = "NIK";
                }

                // menggabungkan lagi menjadi string
                $nik = implode(" ", $nik);

                //pola regex nik
                $pattern = "/(?<=nik ).*/i";

                //mencari string dengan pola diatas. apabila ada string berisi nik maka ambil string selanjutnya
                $isExisted = preg_match($pattern, $nik, $matches);
                if ($isExisted === 1) {
                    $nik = $matches[0];
                    //mereplace string yang berisi nik. kita ambil hanya angka saja
                    $nik = preg_replace("/[^0-9]/", "", $nik);

                    //pola regex 
                    $pattern = "/[0-9]+/i";

                    //mencari string dengan pola diatas. apabila ada string berisi angka maka ambil semua string berisi angka
                    $isExisted = preg_match($pattern, $nik, $matches);
                    if ($isExisted == 1) {
                        $nik = $matches[0];
                        //ambil 16 angka nik terakhir.
                        $nik = substr($nik, -16);
                    } else {
                        //jika tidak ada. maka harus di upload ulang 
                        return response()->json([
                            'status' => false,
                            'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik',
                            'data' => 'backscan'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik',
                        'data' => 'backscan'
                    ]);
                }

                // -----batas Nama-------

                //nama berada di array index ke 3
                $nama = $new_pattern[3];
                //ambil hanya huruf dan spasi
                $nama = preg_replace("/[^a-zA-Z ]/", "", $nama);

                //menghapus spasi didepan dan belakang string
                $nama = trim($nama);

                //merubah string jadi array
                $nama = explode(" ", $nama);

                //jika array index 3 sama senilai lebih 25 persen dari kata "Nama" maka di replace
                similar_text("Nama", $nama[0], $percent);
                if ($percent > 25) {
                    $nama[0] = "Nama";
                }
                //ubah jadi string kembali
                $nama = implode(" ", $nama);

                //pola regex
                $pattern = "/(?<=nama).*/i";
                //jika sesuai dengan pola diatas maka lanjut proses
                $isExisted = preg_match($pattern, $nama, $matches);
                if ($isExisted == 1) {

                    $nama = $matches[0];
                    //pola regex kedua
                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $nama, $attempt);
                    //ubah array jadi string kembali
                    $nama = implode(" ", $attempt[0]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik',
                        'data' => 'backscan'
                    ]);
                }

                // -----batas suci-------

                //polanya yaitu ambil 2 digit angka pertama dari - 2 digit kedua dari - dan 4 digit pertama dari -
                $pattern = "/\d{2} ?- ?\d{2} ?- ?\d{4}/i";

                //tanggal lahir ada di array index ke 4
                $tanggal_lahir = $new_pattern[4];

                //jika ada kata yang sesuai dengan pola maka ambil kata tersebut
                $isExisted = preg_match($pattern, $tanggal_lahir, $matches);
                if ($isExisted == 1) {

                    //jika tanggal lahir tidak kosong
                    if ($tanggal_lahir !== null) {
                        $tanggal_lahir = $matches[0];
                        //menghilangkan spasi
                        $tanggal_lahir = str_replace(" ", "", $tanggal_lahir);
                        //explode berdasarkan -
                        $tanggal_lahir = explode("-", $tanggal_lahir);

                        //mengubah array menjadi terbalik untuk mengikuti format tanggal.
                        $tanggal_lahir = array_reverse($tanggal_lahir);

                        //gabungkan array kembali dengan -
                        $tanggal_lahir = implode("-", $tanggal_lahir);
                    } else {
                        //jika tidak terdeteksi maka tanggal lahir ambil dari nik. dengan syarat 4 digit pertama nik sama dengan kode kota

                        $kode_kota =  substr($nik, 0, 4);
                        $data_kota = City::where('name', 'LIKE', '%' . $kota . '%')->first();
                        $data_kota = $data_kota->code;

                        if ($data_kota ==  $kode_kota) {

                            $tanggal = substr($nik, 6, 2);
                            if ($tanggal > 32) {
                                $tanggal = $tanggal - 40;
                            }
                            $bulan = substr($nik, 8, 2);
                            $tahun = substr($nik, 10, 2);
                            if ($tahun < 25) {
                                $new_tahun = "20" . $tahun;
                            } else {
                                $new_tahun = "19" . $tahun;
                            }
                            $tanggal_lahir = $new_tahun . "-" . $bulan . "-" . $tanggal;
                        } else {
                            $tanggal_lahir = '';
                        }
                    }

                    // -----batas tempat lahir-------

                    //pola regex
                    $pattern = "/(?<=Lahir).*/i";

                    $tempat_lahir_awal = $new_pattern[4];


                    $isExisted =  preg_match($pattern, $tempat_lahir_awal, $matches);
                    if ($isExisted == 1) {
                        $tempat_lahir_awal = $matches[0];

                        $pattern = "/\w+/i";
                        preg_match_all($pattern, $tempat_lahir_awal, $hilih);
                        $tempat_lahir_awal = implode(" ", $hilih[0]);
                        $tempat_lahir = preg_replace("/[^a-zA-Z ]/", "", $tempat_lahir_awal);

                        $tempat_lahir = trim($tempat_lahir);
                    } else {
                        $tempat_lahir = '';
                    }
                } else {

                    $kode_kota =  substr($nik, 0, 4);
                    $data_kota = City::where('name', 'LIKE', '%' . $kota . '%')->first();
                    $data_kota = $data_kota->code;

                    if ($data_kota ==  $kode_kota) {

                        $tanggal = substr($nik, 6, 2);
                        if ($tanggal > 32) {
                            $tanggal = $tanggal - 40;
                        }
                        $bulan = substr($nik, 8, 2);
                        $tahun = substr($nik, 10, 2);
                        if ($tahun < 25) {
                            $new_tahun = "20" . $tahun;
                        } else {
                            $new_tahun = "19" . $tahun;
                        }
                        $tanggal_lahir = $new_tahun . "-" . $bulan . "-" . $tanggal;
                    } else {
                        $tanggal_lahir = '';
                    }

                    $tempat_lahir = "";
                }

                // -----batas suci-------
                $pattern = "/(?<=Darah).*/i";
                $goldar = $new_pattern[5];
                $isExisted = preg_match($pattern, $goldar, $matches);
                if ($isExisted == 1) {

                    $pattern = "/[ABO]+/i";
                    $isExisted = preg_match($pattern, $matches[0], $matches);
                    if ($isExisted == 1) {
                        $golongan_darah = $matches[0];
                    } else {
                        $golongan_darah = '-';
                    }
                } else {
                    $golongan_darah = '-';
                }

                // -----batas suci-------
                $pattern = "/(?<=kelamin).*/i";
                $goldar = $new_pattern[5];
                $isExisted = preg_match($pattern, $goldar, $matches);
                if ($isExisted == 1) {

                    $pattern = "/[LP]+/";
                    $isExisted = preg_match($pattern, $matches[0], $matches);
                    if ($isExisted == 1) {
                        $gender = $matches[0];



                        if ($gender == "p" || $gender == "P") {
                            $gender = 0;
                        } else {
                            $gender = 1;
                        }
                    } else {
                        $jumlah_nik = strlen($nik);
                        if ($jumlah_nik >= 16) {
                            $gender =  substr($nik, 6, 2);
                            $gender = (int)$gender;

                            if ($gender > 32) {
                                $gender = 0;
                            } else {
                                $gender = 1;
                            }
                        } else {
                            $gender = "";
                        }
                    }
                } else {
                    $jumlah_nik = strlen($nik);
                    if ($jumlah_nik >= 16) {
                        $gender =  substr($nik, 6, 2);
                        $gender = (int)$gender;

                        if ($gender > 32) {
                            $gender = 0;
                        } else {
                            $gender = 1;
                        }
                    } else {
                        $gender = "";
                    }
                }

                // -----batas suci-------

                $alamat = $new_pattern[6];

                $alamat = trim($alamat);
                $alamat = explode(" ", $alamat);

                similar_text("Alamat", $alamat[0], $percent);
                if ($percent > 50) {
                    $alamat[0] = "Alamat";
                }
                $alamat = implode(" ", $alamat);



                $pattern = "/(?<=alamat).*/i";
                $isExisted = preg_match($pattern, $alamat, $matches);
                if ($isExisted == 1) {
                    $alamat = $matches[0];
                    $pattern = "/(?=[a-z]).*/i";
                    preg_match_all($pattern, $alamat, $attempt);


                    $alamat = implode(" ", $attempt[0]);
                } else {
                    $alamat = '';
                }
                // -----batas suci-------
                $pattern = "/(?<=kecamatan).*/i";
                $kecamatan = $new_pattern[9];

                $kecamatan = trim($kecamatan);
                $kecamatan = explode(" ", $kecamatan);

                similar_text("Kecamatan", $kecamatan[0], $percent);
                if ($percent > 50) {
                    $kecamatan[0] = "Kecamatan";
                }
                $kecamatan = implode(" ", $kecamatan);
                $isExisted = preg_match($pattern, $kecamatan, $matches);
                if ($isExisted == 1) {
                    $kecamatan = $matches[0];
                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $kecamatan, $attempt);

                    $kecamatan = implode(" ", $attempt[0]);

                    $data_kabupaten = City::where('name', 'LIKE', '%' . $kota . '%')->first();
                    if ($data_kabupaten !== null) {
                        $result = $data_kabupaten->code;
                        $kecamatan_ktp = District::where('city_code', $result)->get();


                        foreach ($kecamatan_ktp as $kecktp) {
                            $abc[] = $kecktp->name;
                        }

                        usort($abc, function ($a, $b) use ($kecamatan) {

                            similar_text($kecamatan, $a, $percentA);
                            similar_text($kecamatan, $b, $percentB);
                            return $percentB - $percentA;
                        });

                        $newkecamatan = $abc[0];

                        similar_text($kecamatan, $newkecamatan, $percent);
                        if ($percent > 50) {
                            $kecamatan = $newkecamatan;
                        }
                    }
                } else {
                    $kecamatan = '';
                }

                // -----batas suci-------
                $pattern = "/(?=[0-9]).*/i";
                $isExisted = preg_match_all($pattern, $new_pattern[7], $hilih);


                // $rtw = explode(":", $new_pattern[7]);
                if ($isExisted == 1) {
                    $rtw = explode("/", $hilih[0][0]);


                    $rt = trim($rtw[0], " ");
                    $rt = preg_replace("/[^0-9]/", "", $rt);
                    $rt = substr($rt, -2);
                    $rt = '0'.$rt;


                    if (isset($rtw[1])) {
                        $rw = trim($rtw[1], " ");
                        $rw = preg_replace("/[^0-9]/", "", $rw);
                        $rw = substr($rw, -2);
                        $rw = '0'.$rw;
                    } else {
                        $rw = '';
                    }
                } else {
                    $rt = '';
                    $rw = '';
                }

                // -----batas suci-------
                $pattern = "/(?<=Desa).*/i";
                $kelurahan = $new_pattern[8];
                $isExisted = preg_match($pattern, $kelurahan, $matches);
                if ($isExisted == 1) {
                    $kelurahan = $matches[0];

                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $kelurahan, $attempt);


                    $kelurahan = implode(" ", $attempt[0]);

                    $data_kecamatan = District::where('name', 'LIKE', '%' . $kecamatan . '%')->first();

                    if ($data_kecamatan !== null) {
                        $result = $data_kecamatan->code;

                        $desa_ktp = Village::where('district_code', $result)->get();


                        foreach ($desa_ktp as $desktp) {
                            $abcd[] = $desktp->name;
                        }

                        usort($abcd, function ($a, $b) use ($kelurahan) {

                            similar_text($kelurahan, $a, $percentA);
                            similar_text($kelurahan, $b, $percentB);
                            return $percentB - $percentA;
                        });

                        $newkelurahan = $abcd[0];
                        similar_text($kelurahan, $newkelurahan, $percent);
                        if ($percent > 75) {
                            $kelurahan = $newkelurahan;
                        }
                    }
                } else {
                    $kelurahan = '';
                }


                // -----batas suci-------
                $pattern = "/(?<=agama).*/i";
                $agama = $new_pattern[10];
                $agama = trim($agama);
                $agama = explode(" ", $agama);

                similar_text("Agama", $agama[0], $percent);
                if ($percent > 50) {
                    $agama[0] = "Agama";
                }
                $agama = implode(" ", $agama);

                $isExisted = preg_match($pattern, $agama, $matches);
                if ($isExisted == 1) {
                    $agama = $matches[0];
                    $pattern = "/[a-z]+/i";
                    preg_match($pattern, $agama, $matches);
                    if (isset($matches[0])) {
                        $agama = $matches[0];


                        $agama_ktp = [
                            'ISLAM',
                            'KRISTEN',
                            'KATOLIK',
                            'BUDHA',
                            'HINDU',
                            'KONGHUCHU'
                        ];
                        usort($agama_ktp, function ($a, $b) use ($agama) {

                            similar_text($agama, $a, $percentA);
                            similar_text($agama, $b, $percentB);
                            return $percentB - $percentA;
                        });
                        $agama = $agama_ktp[0];
                    } else {
                        $agama = '';
                    }
                } else {
                    $agama = '';
                }

                // -----batas suci-------
                $pattern = "/(?<=perkawinan).*/i";
                $perkawinan = $new_pattern[11];

                $isExisted = preg_match($pattern, $perkawinan, $matches);

                if ($isExisted == 1) {
                    $perkawinan = $matches[0];
                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $perkawinan, $attempt);
                    $perkawinan = array_slice($attempt[0], 0, 2);

                    $perkawinan = implode(" ", $perkawinan);

                    $perkawinan_ktp = [
                        'KAWIN',
                        'BELUM KAWIN',
                        'CERAI HIDUP',
                        'CERAI MATI'
                    ];

                    usort($perkawinan_ktp, function ($a, $b) use ($perkawinan) {

                        similar_text($perkawinan, $a, $percentA);
                        similar_text($perkawinan, $b, $percentB);
                        return $percentB - $percentA;
                    });
                    $perkawinan = $perkawinan_ktp[0];
                } else {
                    $perkawinan = '';
                }

                // -----batas suci-------


                $pattern = "/(?<=kerjaan ).*/i";
                $pekerjaan = $new_pattern[12];
                $pekerjaan = trim($pekerjaan);
                $pekerjaan = explode(" ", $pekerjaan);

                similar_text("Pekerjaan", $pekerjaan[0], $percent);
                if ($percent > 50) {
                    $pekerjaan[0] = "Pekerjaan";
                }
                $pekerjaan = implode(" ", $pekerjaan);
                $isExisted = preg_match($pattern, $pekerjaan, $matches);
                if ($isExisted == 1) {
                    $pekerjaan = $matches[0];
                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $pekerjaan, $attempt);

                    $pekerjaan = implode(" ", $attempt[0]);


                    $pekerjaan_ktp =
                        [
                            'MENGURUS RUMAH TANGGA',
                            'BELUM/ TIDAK BEKERJA',
                            'PELAJAR/ MAHASISWA',
                            'PENSIUNAN',
                            'PEGAWAI NEGERI SIPIL',
                            'TENTARA NASIONAL INDONESIA',
                            'KEPOLISISAN RI',
                            'PERDAGANGAN',
                            'PETANI/ PEKEBUN',
                            'PETERNAK',
                            'NELAYAN/ PERIKANAN',
                            'INDUSTRI',
                            'KONSTRUKSI',
                            'TRANSPORTASI',
                            'KARYAWAN SWASTA',
                            'KARYAWAN BUMN',
                            'KARYAWAN BUMD',
                            'KARYAWAN HONORER',
                            'PEGAWAI SWASTA',
                            'BURUH HARIAN LEPAS',
                            'BURUH TANI/ PERKEBUNAN',
                            'BURUH NELAYAN/ PERIKANAN',
                            'BURUH PETERNAKAN',
                            'PEMBANTU RUMAH TANGGA',
                            'TUKANG CUKUR',
                            'TUKANG LISTRIK',
                            'TUKANG BATU',
                            'TUKANG KAYU',
                            'TUKANG SOL SEPATU',
                            'TUKANG LAS/ PANDAI BESI',
                            'TUKANG JAHIT',
                            'TUKANG GIGI',
                            'PENATA RIAS',
                            'PENATA BUSANA',
                            'PENATA RAMBUT',
                            'MEKANIK',
                            'SENIMAN',
                            'TABIB',
                            'PARAJI',
                            'PERANCANG BUSANA',
                            'PENTERJEMAH',
                            'IMAM MASJID',
                            'PENDETA',
                            'PASTOR',
                            'WARTAWAN',
                            'USTADZ/ MUBALIGH',
                            'JURU MASAK',
                            'PROMOTOR ACARA',
                            'ANGGOTA DPR-RI',
                            'ANGGOTA DPD',
                            'ANGGOTA BPK',
                            'PRESIDEN',
                            'WAKIL PRESIDEN',
                            'ANGGOTA MAHKAMAH KONSTITUSI',
                            'ANGGOTA KABINET/ KEMENTERIAN',
                            'DUTA BESAR',
                            'GUBERNUR',
                            'WAKIL GUBERNUR',
                            'BUPATI',
                            'WAKIL BUPATI',
                            'WALIKOTA',
                            'WAKIL WALIKOTA',
                            'ANGGOTA DPRD PROVINSI',
                            'ANGGOTA DPRD KABUPATEN/ KOTA',
                            'DOSEN',
                            'GURU',
                            'PILOT',
                            'PENGACARA',
                            'NOTARIS',
                            'ARSITEK',
                            'AKUNTAN',
                            'KONSULTAN',
                            'DOKTER',
                            'BIDAN',
                            'PERAWAT',
                            'APOTEKER',
                            'PSIKIATER/ PSIKOLOG',
                            'PENYIAR TELEVISI',
                            'PENYIAR RADIO',
                            'PELAUT',
                            'PENELITI',
                            'SOPIR',
                            'PIALANG',
                            'PARANORMAL',
                            'PEDAGANG',
                            'PERANGKAT DESA',
                            'KEPALA DESA',
                            'BIARAWATI',
                            'WIRASWASTA'
                        ];
                    usort($pekerjaan_ktp, function ($a, $b) use ($pekerjaan) {

                        similar_text($pekerjaan, $a, $percentA);
                        similar_text($pekerjaan, $b, $percentB);
                        return $percentB - $percentA;
                    });

                    $pekerjaan = $pekerjaan_ktp[0];
                } else {
                    $pekerjaan = '';
                }

                // -----batas suci-------
                $pattern = "/(?<=negaraan).*/i";
                $kewarganegaraan = $new_pattern[13];
                $kewarganegaraan = trim($kewarganegaraan);
                $kewarganegaraan = explode(" ", $kewarganegaraan);

                similar_text("Kewarganegaraan", $kewarganegaraan[0], $percent);
                if ($percent > 50) {
                    $kewarganegaraan[0] = "Kewarganegaraan";
                }
                $kewarganegaraan = implode(" ", $kewarganegaraan);

                $isExisted = preg_match($pattern, $kewarganegaraan, $matches);
                if ($isExisted == 1) {
                    $kewarganegaraan = $matches[0];
                    $pattern = "/[a-z]+/i";
                    preg_match($pattern, $kewarganegaraan, $attempt);

                    $kewarganegaraan = $attempt[0];

                    similar_text("WNI", $kewarganegaraan[0], $percent);
                    if ($percent > 50) {
                        $kewarganegaraan = "WNI";
                    } elseif (strlen($kewarganegaraan[0]) < 4) {
                        $kewarganegaraan = "WNI";
                    }
                } else {
                    $kewarganegaraan = 'WNI';
                }

                $ktp = [
                    "provinsi" => $provinsi,
                    "kota" => $kota,
                    "nik" => $nik,
                    "nama" => $nama,
                    "tempat_lahir" => $tempat_lahir,
                    "tanggal_lahir" => $tanggal_lahir,
                    "kelamin" => $gender,
                    "golongan_darah" => $golongan_darah,
                    "alamat" => $alamat,
                    "rt" => $rt,
                    "rw" => $rw,
                    "kelurahan" => $kelurahan,
                    "kecamatan" => $kecamatan,
                    "agama" => $agama,
                    "perkawinan" => $perkawinan,
                    "pekerjaan" => $pekerjaan,
                    "kewarganegaraan" => $kewarganegaraan,

                ];


                return response()->json([
                    'status' => true,
                    'message' => 'Upload KTP Sukses',
                    'data' => $ktp
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Mohon Masukkan KTP Terlebih Dahulu',
                'data' => 'backscan'
            ]);
        }
    }

    public function showAll()
    {
        $identity = Identity::query()->get();

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $identity
        ]);
    }

    public function index($id)
    {
        $identity = Identity::select('identity.*', 'users.email')
            ->join('users', 'identity.id_user', '=', 'users.id')
            ->where('identity.id_user', $id)
            ->first();

        if (!$identity) {
            return response()->json([
                "status" => false,
                "message" => "data tidak ditemukan",
                "data" => null
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $identity
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $payload = $request->all();


        $validator = Validator::make($payload, [
            "nik" => 'required|min:16|max:16',
            "nama" => 'required',
            "tempat_lahir" => 'required',
            "tanggal_lahir" => 'required',
            "jenis_kelamin" => 'required|max:1',
            "alamat" => 'required',
            "rt" => 'required|max:3',
            "rw" => 'required|max:3',
            "kelurahan" => 'required',
            "kecamatan" => 'required',
            "kota" => 'required',
            "provinsi" => 'required',
            "agama" => 'required',
            "status_perkawinan" => 'required',
            "pekerjaan" => 'required',
            "kewarganegaraan" => 'required',
            "golongan_darah" => 'required|max:2',
            "ktp" => 'required|mimes:jpg,jpeg,png,heic'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors(),
                "data" => null
            ]);
        }

        $nik = Identity::where('nik', '=', $payload['nik'])->first();
        $identity = Identity::where('id_user', '=', $payload['id_user'])->first();
        // dd($nik,$identity,$payload['nik'],$payload['id_user']);
        $image =  $request->file("ktp");
        $image_name = $image->hashName();
        $image = Image::make($image);
        $width = $image->width();
        $height = $image->height();

        $ukuran_lebar = $width * 25 / 100;
        $ukuran_tinggi = $height * 25 / 100;

        $ukuran_lebar2 = $width * 50 / 100;
        $ukuran_tinggi2 = $height * 50 / 100;

        $ukuran_lebar3 = $width * 75 / 100;
        $ukuran_tinggi3 = $height * 75 / 100;


        $image->text('PROPERTY INI MILIK FARCAPITAL', $ukuran_lebar, $ukuran_tinggi, function ($font) {
            $font->file(public_path("Roboto-Black.ttf"));
            $font->size(40);
            $font->color([255, 255, 255, 0.3]);
            $font->align('center');
            $font->valign('top');
        });
        $image->text('PROPERTY INI MILIK FARCAPITAL', $ukuran_lebar2, $ukuran_tinggi2, function ($font) {
            $font->file(public_path("Roboto-Black.ttf"));
            $font->size(40);
            $font->color([255, 255, 255, 0.3]);
            $font->align('center');
            $font->valign('top');
        });
        $image->text('PROPERTY INI MILIK FARCAPITAL', $ukuran_lebar3, $ukuran_tinggi3, function ($font) {
            $font->file(public_path("Roboto-Black.ttf"));
            $font->size(40);
            $font->color([255, 255, 255, 0.3]);
            $font->align('center');
            $font->valign('top');
        });
        $image->encode('jpg');

        if (!$identity) {
            if($nik){
                return response()->json([
                    "status" => false,
                    "message" => "NIK sudah terdaftar di email atau akun lain.",
                    "data" => 'nik'
                ]);
            }
            Storage::disk('public')->put('images/' . $image_name, $image);
            $payload["ktp"] =  'images/'.$image_name;           
            $identity = Identity::create($payload);

        } else {
            if($payload['nik'] != $identity['nik']){
                return response()->json([
                    "status" => false,
                    "message" => "NIK tidak boleh berubah",
                    "data" => 'nik'
                ]);
            }

            if ($request->hasFile("ktp")) {
                Storage::disk('public')->delete($identity->ktp);

                Storage::disk('public')->put('images/' . $image_name, $image);
                $payload["ktp"] = 'images/'.$image_name;
                $identity->update($payload);
            }

        }
        return response()->json([
            "status" => true,
            "message" => "data berhasil disimpan",
            "data" => $identity
        ]);
    }
}
