<?php

namespace App\Http\Controllers;

use thiagoalessio\TesseractOCR\TesseractOCR;
use OCR;
use App\Models\Identity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use function PHPUnit\Framework\matches;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


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
        $image = $request->file('image');

        if (isset($image)) {

            //konversi oleh tesseract
            $tesseract = new TesseractOCR($image);
            $parsedText = ($tesseract)->dpi(72)->lang('ind')->run();

            //merubah jadi array
            $new_pattern = preg_split('/\n/', $parsedText);

            //menghapus array kosong dan reset index
            $new_pattern = array_values(array_filter($new_pattern));

            if ($new_pattern == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mohon Upload Ulang KTP',

                ]);
            } else {
                //mencari array yang berisi provinsi atau setidaknya paling sama dengan kata provinsi
                $words1 = $new_pattern;
                usort($new_pattern, function ($a, $b) {
                    similar_text('PROVINSI', $a, $percentA);
                    similar_text('PROVINSI', $b, $percentB);
                    return $percentB - $percentA;
                });
                $cutter = array_search($new_pattern[0], $words1);
                $new_pattern = array_slice($words1, $cutter);

                //apabila isi array kurang dari 13 maka konversi diulang
                if (count($new_pattern) <= 13) {
                    return response()->json([
                        'status' => false,
                        'message' => 'ktp tidak terdeteksi'
                    ]);
                } else {
                    //mencari provinsi
                    $provinsi_baru = $new_pattern[0];

                    $a = explode(" ", $provinsi_baru);

                    $a[0] = 'PROVINSI';

                    $b = implode(" ", $a);
                    $provinsi = $b;



                    $pattern = "/(?<=provinsi ).*/i";
                    $isExisted = preg_match($pattern, $provinsi, $matches);
                    if ($isExisted == 1) {
                        $provinsi = $matches[0];
                        $provinsi = preg_replace("/[^a-zA-Z ]/", "", $provinsi);
                        $provinsi = trim($provinsi);
                    } else {
                        $provinsi = '-';
                    }

                    // -----batas suci-------
                    $kota = $new_pattern[1];
                    $kota = preg_replace("/[^a-zA-Z ]/", "", $kota);
                    $kota = trim($kota);

                    // -----batas suci-------

                    $nik = $new_pattern[2];

                    $nik = preg_replace("/[^a-zA-Z0-9 ]/", "", $nik);
                    $nik = trim($nik);
                    $nik = explode(" ", $nik);

                    similar_text("NIK", $nik[0], $percent);
                    if ($percent > 50) {
                        $nik[0] = "NIK";
                    }
                    $nik = implode(" ", $nik);


                    $pattern = "/(?<=nik ).*/i";
                    $isExisted = preg_match($pattern, $nik, $matches);
                    if ($isExisted === 1) {

                        $a = explode(" ", $matches[0]);
                        $nik = end($a);
                        $pattern = "/[0-9]+/i";
                        $isExisted = preg_match($pattern, $nik, $matches);
                        if ($isExisted == 1) {
                            $nik = $matches[0];
                        } else {
                            return response()->json([
                                'status' => false,
                                'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik ya'
                            ]);
                        }
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik ya'
                        ]);
                    }


                    // -----batas suci-------

                    $nama = $new_pattern[3];
                    $nama = preg_replace("/[^a-zA-Z ]/", "", $nama);
                    $nama = trim($nama);
                    $nama = explode(" ", $nama);

                    similar_text("Nama", $nama[0], $percent);
                    if ($percent > 50) {
                        $nama[0] = "Nama";
                    }
                    $nama = implode(" ", $nama);


                    $pattern = "/(?<=nama).*/i";
                    $isExisted = preg_match($pattern, $nama, $matches);
                    if ($isExisted == 1) {
                        $nama = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match_all($pattern, $nama, $attempt);

                        $nama = implode(" ", $attempt[0]);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik'
                        ]);
                    }



                    // -----batas suci-------
                    $pattern = "/\d{2} ?- ?\d{2} ?- ?\d{4}/i";
                    $tanggal_lahir = $new_pattern[4];


                    $isExisted = preg_match($pattern, $tanggal_lahir, $matches);
                    if ($isExisted == 1) {



                        if ($tanggal_lahir !== null) {
                            $tanggal_lahir = $matches[0];
                            $tanggal_lahir = str_replace(" ", "", $tanggal_lahir);
                            $tanggal_lahir = explode("-", $tanggal_lahir);
                            $tanggal_lahir = array_reverse($tanggal_lahir);
                            $tanggal_lahir = implode("-", $tanggal_lahir);
                        } else {
                            $tanggal_lahir = '-';
                        }

                        // -----batas suci-------

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
                            $tempat_lahir = '-';
                        }
                    } else {
                        $tanggal_lahir = "-";
                        $tempat_lahir = "-";
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=Darah).*/i";
                    $goldar = $new_pattern[5];
                    $isExisted = preg_match($pattern, $goldar, $matches);
                    if ($isExisted == 1) {
                        // dd($matches);
                        $pattern = "/[ABO-]+/i";
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
                        // dd($matches);
                        $pattern = "/[LP]+/";
                        $isExisted = preg_match($pattern, $matches[0], $matches);
                        if ($isExisted == 1) {
                            $gender = $matches[0];

                            // dd($gender);

                            if ($gender == "p" || $gender == "P") {
                                $gender = 0;
                            } else {
                                $gender = 1;
                            }
                        } else {
                            $gender = '-';
                        }
                    } else {
                        $gender = '-';
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
                        $alamat = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?=[0-9]).*/i";
                    $isExisted = preg_match_all($pattern, $new_pattern[7], $hilih);
                    // $rtw = explode(":", $new_pattern[7]);
                    if ($isExisted == 1) {
                        $rtw = explode("/", $hilih[0][0]);

                        $rt = trim($rtw[0], " ");
                        $rt = substr($rt, -3);

                        if (isset($rtw[1])) {
                            $rw = trim($rtw[1], " ");
                            $rw = substr($rw, -3);
                        } else {
                            $rw = '-';
                        }
                    } else {
                        $rt = '-';
                        $rw = '-';
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
                    } else {
                        $kelurahan = '-';
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
                    } else {
                        $kecamatan = '-';
                    }
                    // $kec = explode(":", $new_pattern[9]);
                    // $kec = trim($kec[1], " "); //RIP
                    // $new_pattern[9] = $kec;

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
                    // dd($agama);
                    $isExisted = preg_match($pattern, $agama, $matches);
                    if ($isExisted == 1) {
                        $agama = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match($pattern, $agama, $matches);
                        if (isset($matches[0])) {
                            $agama = $matches[0];
                        } else {
                            $agama = '-';
                        }
                    } else {
                        $agama = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=perkawinan).*/i";
                    $perkawinan = $new_pattern[11];

                    $isExisted = preg_match($pattern, $perkawinan, $matches);

                    if ($isExisted == 1) {
                        $perkawinan = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match_all($pattern, $perkawinan, $attempt);

                        $perkawinan = implode(" ", $attempt[0]);
                    } else {
                        $perkawinan = '-';
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
                    } else {
                        $pekerjaan = '-';
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
                    } else {
                        $kewarganegaraan = '-';
                    }
                    // dd($new_pattern);
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
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Mohon Masukkan KTP terlebih dahulu'
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
        $identity = Identity::where('id_user', $id)->first();

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

        $identity = Identity::where('nik', '=', $payload['nik'])->first();

        if (!$identity) {
            $payload["ktp"] = $request->file("ktp")->store("images", "public");
            $identity = Identity::create($payload);
        } else {
            if ($request->hasFile("ktp")) {
                Storage::disk('public')->delete($identity->ktp);                            // hapus foto ktp sebelumnya
                $payload["ktp"] = $request->file("ktp")->store("images", "public");
            }
            $identity->update($payload);
        }

        return response()->json([
            "status" => true,
            "message" => "data berhasil disimpan",
            "data" => $identity
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
