<?php

namespace App\Http\Controllers;

use Alimranahmed\LaraOCR\Services\OcrAbstract;
use OCR;
use function PHPUnit\Framework\matches;
use App\Http\Controllers\Controller;
use App\Models\Identity;
use Illuminate\Http\Request;
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
        $image = $request->image;

        if (isset($image) && $image->getPathName()) {
            $ocr = app()->make(OcrAbstract::class);
            $parsedText = $ocr->scan($image->getPathName());


            $pattern = '/provinsi/i';
            $checkProvinsi = preg_match($pattern, $parsedText, $matches);
            $new_pattern = preg_split('/\n/', $parsedText);
            $new_pattern = array_values(array_filter($new_pattern));

            if ($new_pattern == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mohon Upload Ulang KTP',

                ]);
            } else {

                $words1 = $new_pattern;
                usort($new_pattern, function ($a, $b) {
                    similar_text('PROVINSI', $a, $percentA);
                    similar_text('PROVINSI', $b, $percentB);
                    return $percentB - $percentA;
                });
                $cutter = array_search($new_pattern[0], $words1);
                $new_pattern = array_slice($words1, $cutter);

                $provinsi = $new_pattern[0];
                if ($checkProvinsi) {



                    $pattern = "/(?<=provinsi ).*/i";
                    $isExisted = preg_match($pattern, $provinsi, $matches);
                    if ($isExisted == 1) {
                        $provinsi = $matches[0];
                    } else {
                        $provinsi = '-';
                    }

                    // -----batas suci-------
                    $kota = $new_pattern[1];

                    // -----batas suci-------
                    $pattern = "/(?<=nik ).*/i";
                    $nik = $new_pattern[2];
                    $isExisted = preg_match($pattern, $nik, $matches);
                    if ($isExisted === 1) {
                        $nik = $matches[0];
                        $pattern = "/[0-9]+/i";
                        $isExisted = preg_match($pattern, $nik, $matches);
                        if ($isExisted == 1) {
                            $nik = $matches[0];
                        } else {
                            $nik = '-';
                        }
                    } else {
                        $nik = '-';
                    }


                    // -----batas suci-------
                    $pattern = "/(?<=nama ).*/i";
                    $nama = $new_pattern[3];
                    $isExisted = preg_match($pattern, $nama, $matches);
                    if ($isExisted == 1) {
                        $nama = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match_all($pattern, $nama, $anjay);

                        $nama = implode(" ", $anjay[0]);
                    } else {
                        $nama = '-';
                    }



                    // -----batas suci-------
                    $pattern = "/\d{2}-\d{2}-\d{4}/i";
                    $tanggal_lahir = $new_pattern[4];
                    $isExisted = preg_match($pattern, $tanggal_lahir, $matches);
                    if ($isExisted == 1) {
                        $tanggal_lahir = $matches[0];

                        if ($tanggal_lahir !== null) {
                            $tanggal_lahir = $matches[0];
                        } else {
                            $tanggal_lahir = '-';
                        }

                        // -----batas suci-------
                        $pattern = "/(?<=Lahir).*(?=" . $tanggal_lahir . ")/i";
                        $tempat_lahir = $new_pattern[4];
                        $isExisted =  preg_match($pattern, $tempat_lahir, $matches);
                        if ($isExisted == 1) {
                            $tempat_lahir = $matches[0];
                            $pattern = "/\w+/i";
                            preg_match_all($pattern, $tempat_lahir, $hilih);
                            $tempat_lahir = implode(" ", $hilih[0]);
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
                                $gender = 'PEREMPUAN';
                            } else {
                                $gender = "LAKI-LAKI";
                            }
                        } else {
                            $gender = '-';
                        }
                    } else {
                        $gender = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=alamat).*/i";
                    $alamat = $new_pattern[6];
                    $isExisted = preg_match($pattern, $alamat, $matches);
                    if ($isExisted == 1) {
                        $alamat = $matches[0];
                        $pattern = "/(?=[a-z]).*/i";
                        preg_match_all($pattern, $alamat, $anjay);


                        $alamat = implode(" ", $anjay[0]);
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
                        $rw = trim($rtw[1], " ");
                    } else {
                        $rt = '-';
                        $rw = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=Desa ).*/i";
                    $kelurahan = $new_pattern[8];
                    $isExisted = preg_match($pattern, $kelurahan, $matches);
                    if ($isExisted == 1) {
                        $kelurahan = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match_all($pattern, $kelurahan, $anjay);


                        $kelurahan = implode(" ", $anjay[0]);
                    } else {
                        $kelurahan = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=camatan ).*/i";
                    $kecamatan = $new_pattern[9];
                    $isExisted = preg_match($pattern, $kecamatan, $matches);
                    if ($isExisted == 1) {
                        $kecamatan = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match_all($pattern, $kecamatan, $anjay);

                        $kecamatan = implode(" ", $anjay[0]);
                    } else {
                        $kecamatan = '-';
                    }
                    // $kec = explode(":", $new_pattern[9]);
                    // $kec = trim($kec[1], " "); //RIP
                    // $new_pattern[9] = $kec;

                    // -----batas suci-------
                    $pattern = "/(?<=agama ).*/i";
                    $agama = $new_pattern[10];
                    // dd($agama);
                    $isExisted = preg_match($pattern, $agama, $matches);
                    if ($isExisted == 1) {
                        $agama = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match($pattern, $agama, $matches);
                        $agama = $matches[0];
                    } else {
                        $agama = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=kawinan ).*/i";
                    $perkawinan = $new_pattern[11];
                    $isExisted = preg_match($pattern, $perkawinan, $matches);
                    if ($isExisted == 1) {
                        $perkawinan = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match_all($pattern, $perkawinan, $anjay);

                        $perkawinan = implode(" ", $anjay[0]);
                    } else {
                        $perkawinan = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=kerjaan ).*/i";
                    $pekerjaan = $new_pattern[12];
                    $isExisted = preg_match($pattern, $pekerjaan, $matches);
                    if ($isExisted == 1) {
                        $pekerjaan = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match_all($pattern, $pekerjaan, $anjay);

                        $pekerjaan = implode(" ", $anjay[0]);
                    } else {
                        $pekerjaan = '-';
                    }

                    // -----batas suci-------
                    $pattern = "/(?<=negaraan ).*/i";
                    $kewarganegaraan = $new_pattern[13];
                    $isExisted = preg_match($pattern, $kewarganegaraan, $matches);
                    if ($isExisted == 1) {
                        $kewarganegaraan = $matches[0];
                        $pattern = "/[a-z]+/i";
                        preg_match($pattern, $kewarganegaraan, $anjay);

                        $kewarganegaraan = $anjay[0];
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
                        "kewarganegaraan" => $kewarganegaraan
                    ];

                    return response()->json([
                        'status' => true,
                        'message' => 'succes',
                        'data' => $ktp
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik'
                    ]);
                }
            }
        }
    }


    public function index()
    {
        return response()->json([
            'status' => true,
            'message' => 'succes',
            'data' => 'ini contoh'
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
        $validator = Validator::make($request->all(),[
            "nik" => 'required|min:16|max:16',
            "nama" => 'required',
            "tempat_lahir" => 'required',
            "tanggal_lahir" => 'required|date',
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
            "golongan_darah" => 'required|max:2'
        ]);
        
        if($validator->fails()){
            return response()->json([
                "status" => false,
                "message" => $validator->errors(),
                "data" => null
            ]);
        }
        $payload = $request->all();

        $count = Identity::where('nik', '=', $payload['nik'])->count();

        if($count == 0){
            $identity = Identity::query()->create($payload);
        }else{

            $query = Identity::query()
            ->select('id')
            ->where("nik", $payload['nik'])
            ->first();
            $identity = Identity::find($query['id'])->update($payload);
        }
        

        return response()->json([
            "status" => true,
            "message" => "data tersimpan",
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
