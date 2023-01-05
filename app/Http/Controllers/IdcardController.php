<?php

namespace App\Http\Controllers;

use Alimranahmed\LaraOCR\Services\OcrAbstract;
use OCR;
use function PHPUnit\Framework\matches;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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


            $newParsedText = preg_split('/\n/', $parsedText);
            $newParsedText = array_values(array_filter($newParsedText));
            $provinsi = $newParsedText[0];

            $cek_provinsi = explode(" ", $provinsi);


            if ($cek_provinsi[0] == 'PROVINSI') {


                $pattern = "/(?<=provinsi ).*/i";
                preg_match($pattern, $provinsi, $matches);
                $provinsi = $matches[0];

                // -----batas suci-------
                $kota = $newParsedText[1];

                // -----batas suci-------
                $pattern = "/(?<=nik ).*/i";
                $nik = $newParsedText[2];
                preg_match($pattern, $nik, $matches);
                $nik = $matches[0];
                $pattern = "/[0-9]+/i";
                preg_match($pattern, $nik, $matches);
                $nik = $matches[0];

                // -----batas suci-------
                $pattern = "/(?<=nama ).*/i";
                $nama = $newParsedText[3];
                preg_match($pattern, $nama, $matches);
                $nama = $matches[0];
                $pattern = "/[a-z]+/i";
                preg_match_all($pattern, $nama, $anjay);

                $nama = implode(" ", $anjay[0]);

                // -----batas suci-------
                $pattern = "/\d{2}-\d{2}-\d{4}/i";
                $tanggal_lahir = $newParsedText[4];
                preg_match($pattern, $tanggal_lahir, $matches);
                $tanggal_lahir = $matches[0];

                // -----batas suci-------
                $pattern = "/(?<=Lahir).*(?=" . $tanggal_lahir . ")/i";
                $tempat_lahir = $newParsedText[4];
                preg_match($pattern, $tempat_lahir, $matches);
                $tempat_lahir = $matches[0];
                $pattern = "/\w+/i";
                preg_match_all($pattern, $tempat_lahir, $hilih);
                $tempat_lahir = implode(" ", $hilih[0]);

                // -----batas suci-------
                $pattern = "/(?<=Darah).*/i";
                $goldar = $newParsedText[5];
                preg_match($pattern, $goldar, $matches);

                $pattern = "/[ABO-]+/i";
                preg_match($pattern, $matches[0], $matches);
                $golongan_darah = $matches[0];

                // -----batas suci-------
                $pattern = "/(?<=kelamin).*/i";
                $goldar = $newParsedText[5];
                preg_match($pattern, $goldar, $matches);
                // dd($matches);
                $pattern = "/[LP]+/";
                preg_match($pattern, $matches[0], $matches);
                $gender = $matches[0];
                // dd($gender);

                if ($gender == "p" || $gender == "P") {
                    $gender = 'PEREMPUAN';
                } else {
                    $gender = "LAKI-LAKI";
                }

                // -----batas suci-------
                $pattern = "/(?<=alamat).*/i";
                $alamat = $newParsedText[6];
                preg_match($pattern, $alamat, $matches);
                $alamat = $matches[0];
                $pattern = "/(?=[a-z]).*/i";
                preg_match_all($pattern, $alamat, $anjay);

                $alamat = implode(" ", $anjay[0]);

                // -----batas suci-------
                $pattern = "/(?=[0-9]).*/i";
                preg_match_all($pattern, $newParsedText[7], $hilih);
                // $rtw = explode(":", $newParsedText[7]);
                $rtw = explode("/", $hilih[0][0]);
                $rt = trim($rtw[0], " ");
                $rw = trim($rtw[1], " ");

                // -----batas suci-------
                $pattern = "/(?<=Desa ).*/i";
                $kelurahan = $newParsedText[8];
                preg_match($pattern, $kelurahan, $matches);
                $kelurahan = $matches[0];
                $pattern = "/[a-z]+/i";
                preg_match_all($pattern, $kelurahan, $anjay);

                $kelurahan = implode(" ", $anjay[0]);

                // -----batas suci-------
                $pattern = "/(?<=camatan ).*/i";
                $kecamatan = $newParsedText[9];
                preg_match($pattern, $kecamatan, $matches);
                $kecamatan = $matches[0];
                $pattern = "/[a-z]+/i";
                preg_match_all($pattern, $kecamatan, $anjay);

                $kecamatan = implode(" ", $anjay[0]);
                // $kec = explode(":", $newParsedText[9]);
                // $kec = trim($kec[1], " "); //RIP
                // $newParsedText[9] = $kec;

                // -----batas suci-------
                $pattern = "/(?<=agama ).*/i";
                $agama = $newParsedText[10];
                // dd($agama);
                preg_match($pattern, $agama, $matches);
                $agama = $matches[0];
                $pattern = "/[a-z]+/i";
                preg_match($pattern, $agama, $matches);
                $agama = $matches[0];
                $newParsedText[10] = $agama;

                // -----batas suci-------
                $pattern = "/(?<=kawinan ).*/i";
                $perkawinan = $newParsedText[11];
                preg_match($pattern, $perkawinan, $matches);
                $perkawinan = $matches[0];
                $pattern = "/[a-z]+/i";
                preg_match_all($pattern, $perkawinan, $anjay);

                $perkawinan = implode(" ", $anjay[0]);

                // -----batas suci-------
                $pattern = "/(?<=kerjaan ).*/i";
                $pekerjaan = $newParsedText[12];
                preg_match($pattern, $pekerjaan, $matches);
                $pekerjaan = $matches[0];
                $pattern = "/[a-z]+/i";
                preg_match_all($pattern, $pekerjaan, $anjay);

                $pekerjaan = implode(" ", $anjay[0]);

                // -----batas suci-------
                $pattern = "/(?<=negaraan ).*/i";
                $kewarganegaraan = $newParsedText[13];
                preg_match($pattern, $kewarganegaraan, $matches);
                $kewarganegaraan = $matches[0];
                $pattern = "/[a-z]+/i";
                preg_match($pattern, $kewarganegaraan, $anjay);

                $kewarganegaraan = $anjay[0];
                // dd($newParsedText);
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
                    'message' => 'Upload file berhasil',
                    'data' => $ktp
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Mohon Upload Ulang KTP anda',
                    'data' => null
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'failed',
                'data' => null
            ]);
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
        //
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
