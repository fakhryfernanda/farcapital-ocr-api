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
        //take image from request
        $image = $request->file('image');


        if (isset($image)) {

            //conversion by tesseract

            $tesseract = new TesseractOCR($image);
            $parsedText = ($tesseract)->dpi(72)->lang('ind')->run();

            // convert to array
            $new_pattern = preg_split('/\n/', $parsedText);

            //removes empty arrays and resets index
            $new_pattern = array_filter($new_pattern);
            $new_pattern = \array_diff($new_pattern, [" "]);
            $new_pattern = array_values($new_pattern);

            //if the length of the array are less than 14 then the conversion is repeated by changing the image to greyscale and adding contrast and brightness
            if (count($new_pattern) <= 13) {

                //changing the image to greyscale and adding contrast and brightness
                $image = Image::make($image)->greyscale()->contrast(10)->brightness(20);

                //save temporary image
                $image->save('greyscale/bar.jpg');

                //conversion by tesseract
                $tesseract = new TesseractOCR('greyscale/bar.jpg');
                $parsedText = ($tesseract)->dpi(72)->run();

                //convert to array based on line
                $new_pattern = preg_split('/\n/', $parsedText);

                //removes empty arrays and resets index
                $new_pattern = array_filter($new_pattern);
                $new_pattern = \array_diff($new_pattern, [" "]);
                $new_pattern = array_values($new_pattern);

                //delete the image
                unlink('greyscale/bar.jpg');


                //if the length of the array has not reached 14 then we repeat the scan process by adding a contrast of 5% in four stages
                if (count($new_pattern) <= 13) {

                    $image = Image::make($image)->greyscale()->contrast(15)->brightness(20);

                    $image->save('greyscale/bar.jpg');


                    $tesseract = new TesseractOCR('greyscale/bar.jpg');
                    $parsedText = ($tesseract)->dpi(72)->run();


                    $new_pattern = preg_split('/\n/', $parsedText);


                    $new_pattern = array_filter($new_pattern);
                    $new_pattern = \array_diff($new_pattern, [" "]);
                    $new_pattern = array_values($new_pattern);

                    unlink('greyscale/bar.jpg');


                    if (count($new_pattern) <= 13) {

                        $image = Image::make($image)->greyscale()->contrast(20)->brightness(20);

                        $image->save('greyscale/bar.jpg');


                        $tesseract = new TesseractOCR('greyscale/bar.jpg');
                        $parsedText = ($tesseract)->dpi(72)->run();


                        $new_pattern = preg_split('/\n/', $parsedText);


                        $new_pattern = array_filter($new_pattern);
                        $new_pattern = \array_diff($new_pattern, [" "]);
                        $new_pattern = array_values($new_pattern);

                        unlink('greyscale/bar.jpg');
                        if (count($new_pattern) <= 13) {

                            $image = Image::make($image)->greyscale()->contrast(25)->brightness(20);

                            $image->save('greyscale/bar.jpg');


                            $tesseract = new TesseractOCR('greyscale/bar.jpg');
                            $parsedText = ($tesseract)->dpi(72)->lang('ind')->userWords('user.txt')->run();


                            $new_pattern = preg_split('/\n/', $parsedText);


                            $new_pattern = array_values(array_filter($new_pattern));

                            unlink('greyscale/bar.jpg');

                            if (count($new_pattern) <= 13) {

                                $image = Image::make($image)->greyscale()->contrast(30)->brightness(20);

                                $image->save('greyscale/bar.jpg');


                                $tesseract = new TesseractOCR('greyscale/bar.jpg');
                                $parsedText = ($tesseract)->dpi(72)->lang('ind')->userWords('user.txt')->run();


                                $new_pattern = preg_split('/\n/', $parsedText);


                                $new_pattern = array_filter($new_pattern);
                                $new_pattern = \array_diff($new_pattern, [" "]);
                                $new_pattern = array_values($new_pattern);

                                unlink('greyscale/bar.jpg');

                                if (count($new_pattern) == 0) {

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



            //find an array that contains the province or at least equals the word province
            $words1 = $new_pattern;
            usort($new_pattern, function ($a, $b) {
                similar_text('PROVINSI', $a, $percentA);
                similar_text('PROVINSI', $b, $percentB);
                return $percentB - $percentA;
            });

            //if it is found then the previous array will be cut
            $cutter = array_search($new_pattern[0], $words1);
            $new_pattern = array_slice($words1, $cutter);


            //checking a similarity of array
            $checkprovinsi = explode(" ", $new_pattern[0]);

            usort($checkprovinsi, function ($a, $b) {
                similar_text('PROVINSI', $a, $percentA);
                similar_text('PROVINSI', $b, $percentB);
                return $percentB - $percentA;
            });

            //if the similarity is less than 35 percent then there will be a rejection response
            similar_text($checkprovinsi[0], "PROVINSI", $percent);
            if ($percent < 35) {

                return response()->json([
                    'status' => false,
                    'message' => 'KTP Tidak Terdeteksi, Mohon Upload ulang.',
                    'data' => 'backscan'
                ]);
            } else {

                $provinsi_baru = $new_pattern[0];

                //explode array to replace imperfect province word.

                $a = explode(" ", $provinsi_baru);
                $a[0] = 'PROVINSI';

                //provinces merged again
                $b = implode(" ", $a);
                $provinsi = $b;

                //create the regex pattern
                $pattern = "/(?<=provinsi ).*/i";

                // search string with pattern above. if there is a string containing province then take the next string
                $isExisted = preg_match($pattern, $provinsi, $matches);

                //if true
                if ($isExisted == 1) {
                    $provinsi = $matches[0];

                    //replace words and only letters and spaces are taken. otherwise discarded
                    $provinsi = preg_replace("/[^a-zA-Z ]/", "", $provinsi);

                    //removes leading and trailing spaces
                    $provinsi = trim($provinsi);


                    //data from the database to replace words after the province which is still not quite right
                    $provinsi_ktp = Province::all();
                    foreach ($provinsi_ktp as $provinsiktp) {
                        $ktpprovinsi[] = $provinsiktp->name;
                    }

                    //search for the province word that is most similar to the data obtained from the tesseract scan
                    usort($ktpprovinsi, function ($a, $b) use ($provinsi) {
                        similar_text($provinsi, $a, $percentA);
                        similar_text($provinsi, $b, $percentB);
                        return $percentB - $percentA;
                    });
                    $newprovinsi = $ktpprovinsi[0];
                    // if the identity card equation is more than 50%, then the identity card data is replaced with the data obtained from the array
                    similar_text($provinsi, $newprovinsi, $percent);
                    if ($percent > 50) {
                        $provinsi = $newprovinsi;
                    }
                } else {
                    // if tesseract cannot detect the word after province then the response is rejection
                    return response()->json([
                        'status' => false,
                        'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik',
                        'data' => 'backscan'
                    ]);
                }

                // -----city limits-------
                if (count($new_pattern) > 2) {
                    //array index 1 contains cities
                    $kota = $new_pattern[1];

                    // get a string containing only letters and spaces
                    $kota = preg_replace("/[^a-zA-Z ]/", "", $kota);

                    //removes leading and trailing spaces in the string
                    $kota = trim($kota);

                    //take the province code to be the search parameter in the city table
                    $data_provinsi = Province::where('name', 'LIKE', '%' . $provinsi . '%')->first();

                    //if the province data is empty then the word city is not replaced. if not empty then proceed to the next process
                    if ($data_provinsi !== null) {
                        $result = $data_provinsi->code;

                        //find city array according to province code
                        $kota_ktp = City::where('province_code', $result)->get();


                        foreach ($kota_ktp as $kotaktp) {
                            $ktpkota[] = $kotaktp->name;
                        }

                        // search for city words that are most similar to the data obtained from the tesseract scan
                        usort($ktpkota, function ($a, $b) use ($kota) {
                            similar_text($kota, $a, $percentA);
                            similar_text($kota, $b, $percentB);
                            return $percentB - $percentA;
                        });

                        $newkota = $ktpkota[0];
                        //if the 0th array that is obtained above the equation is more than 50 percent then the city is replaced. if not equal then response with rejection
                        similar_text($kota, $newkota, $percent);
                        if ($percent > 50) {
                            $kota = $newkota;
                        } else {
                            return response()->json([
                                'status' => false,
                                'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik',
                                'data' => 'backscan'
                            ]);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik',
                        'data' => 'backscan'
                    ]);
                }



                //function to search for the desired data. such as NIK, name, address and others
                function get_first_word($sentence)
                {

                    return explode(' ', trim(preg_replace("/[^a-zA-Z0-9 ]/", "", $sentence)))[0];
                }

                function compare_first_words_to_keyword($a, $b, $keyword)
                {
                    similar_text(get_first_word($a), $keyword, $percent_a);
                    similar_text(get_first_word($b), $keyword, $percent_b);
                    return $percent_b - $percent_a;
                }

                // -----batas NIK-------

                //find data containing NIK
                $sentences = $new_pattern;
                $keyword = "NIK";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });
                $nik = $sentences[0];

                //cleans the string containing nik. and only letters, spaces and numbers are taken.

                $nik = preg_replace("/[^a-zA-Z0-9 ]/", "", $nik);

                // clear leading and trailing spaces of the string
                $nik = trim($nik);

                //converts the string containing nik into an array
                $nik = explode(" ", $nik);

                //array index 0 must contain the word NIK. checked with similar text.
                // if the array is more than 50% similar then we will replace array 0 with NIK
                //so we can do the next check.
                similar_text("NIK", $nik[0], $percent);
                if ($percent > 30) {
                    $nik[0] = "NIK";
                }

                // concatenate again into a string
                $nik = implode(" ", $nik);

                // regex pattern NIK
                $pattern = "/(?<=nik ).*/i";

                // search string with pattern above. if there is a string containing nik then take the next string
                $isExisted = preg_match($pattern, $nik, $matches);
                if ($isExisted === 1) {
                    $nik = $matches[0];

                    //replace string containing NIK. take only numbers
                    $nik = preg_replace("/[^0-9]/", "", $nik);

                    //regex Pattern
                    $pattern = "/[0-9]+/i";

                    // search string with pattern above. if there is a string containing numbers then get all strings containing numbers
                    $isExisted = preg_match($pattern, $nik, $matches);
                    if ($isExisted == 1) {
                        $nik = $matches[0];

                        //take the last 16 digits of NIK.
                        $nik = substr($nik, -16);
                    } else {

                        //if no string contains number then generate reupload response
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

                //-----Name limit-------

                //search name in array  with keyword nama
                $keyword = "Nama";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });
                $nama = $sentences[0];


                //take only letters and spaces
                $nama = preg_replace("/[^a-zA-Z ]/", "", $nama);

                //removes leading and trailing spaces in the string
                $nama = trim($nama);

                // convert string to array
                $nama = explode(" ", $nama);

                //if the contents of $name[0] are equal to more than 25 percent of the word "Name" then it is replaced
                similar_text("Nama", $nama[0], $percent);
                if ($percent > 25) {
                    $nama[0] = "Nama";
                }

                //convert to string again
                $nama = implode(" ", $nama);

                //regex pattern
                $pattern = "/(?<=nama).*/i";

                //if it matches the pattern above then continue the process
                $isExisted = preg_match($pattern, $nama, $matches);
                if ($isExisted == 1) {

                    $nama = $matches[0];

                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $nama, $attempt);


                    $nama = implode(" ", $attempt[0]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Mohon Upload Ulang KTP dengan Kualitas yang lebih baik',
                        'data' => 'backscan'
                    ]);
                }

                // -----Tanggal lahir-------

                //search date and place of birthday in array  with keyword "tempat"
                $keyword = "Tempat";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });


                $tanggal_lahir = $sentences[0];

                //the pattern is to take the first 2 digits of - the second 2 digits of - and the first 4 digits of -
                $pattern = "/\d{2} ?- ?\d{2} ?- ?\d{4}/i";



                //if there is a number that matches the pattern then take that number
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

                    $tempat_lahir_awal = $sentences[0];
                    //ambil tanggal lahir

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

                    //if date of birth is blank. we can take the date of birth from the 7th and 8th numbers in the NIK. with
                    //the provision of. the previous number corresponds to the city code. as a comparison and proof if the NIK is correct
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



                //collect blood group data
                $keyword = "Jenis";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });


                $goldar = $sentences[0];

                $pattern = "/(?<=Darah).*/i";
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
                //get gender data

                $pattern = "/(?<=kelamin).*/i";
                $goldar = $sentences[0];
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

                    //if data is empty. we can take the gender from the NIK data. Same as date of birth
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


                //get address data
                $keyword = "Alamat";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });


                $alamat = $sentences[0];


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


                // get district data
                $keyword = "Kecamatan";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });

                $kecamatan = $sentences[0];

                $pattern = "/(?<=kecamatan).*/i";

                $kecamatan = trim($kecamatan);
                $kecamatan = explode(" ", $kecamatan);

                similar_text("Kecamatan", $kecamatan[0], $percent);
                if ($percent > 30) {
                    $kecamatan[0] = "Kecamatan";
                }
                $kecamatan = implode(" ", $kecamatan);
                $isExisted = preg_match($pattern, $kecamatan, $matches);
                if ($isExisted == 1) {
                    $kecamatan = $matches[0];
                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $kecamatan, $attempt);

                    $kecamatan = implode(" ", $attempt[0]);

                    //check the sub-district name from the database
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

                //get RT & RW data-------

                $keyword = "RT";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });


                $pattern = "/(?=[0-9]).*/i";
                $isExisted = preg_match_all($pattern, $sentences[0], $rtrw);


                // $rtw = explode(":", $new_pattern[7]);
                if ($isExisted == 1) {
                    $rtw = explode("/", $rtrw[0][0]);


                    $rt = trim($rtw[0], " ");
                    $rt = preg_replace("/[^0-9]/", "", $rt);
                    $rt = substr($rt, -2);
                    $rt = '0' . $rt;


                    if (isset($rtw[1])) {
                        $rw = trim($rtw[1], " ");
                        $rw = preg_replace("/[^0-9]/", "", $rw);
                        $rw = substr($rw, -2);
                        $rw = '0' . $rw;
                    } else {
                        $rw = '';
                    }
                } else {
                    $rt = '';
                    $rw = '';
                }

                // -- get village data-------

                $keyword = "Kel/Desa";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });
                $pattern = "/(?<=Desa).*/i";
                $kelurahan = $sentences[0];
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


                // -----get religious data-------
                $keyword = "Agama";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });
                $pattern = "/(?<=agama).*/i";
                $agama = $sentences[0];
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


                // -----get marital status-------

                $keyword = "Status";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });
                $pattern = "/(?<=perkawinan).*/i";
                $perkawinan = $sentences[0];

                $isExisted = preg_match($pattern, $perkawinan, $matches);

                if ($isExisted == 1) {
                    $perkawinan = $matches[0];
                    $pattern = "/[a-z]+/i";
                    preg_match_all($pattern, $perkawinan, $attempt);
                    //mengambil 2 kata dari array
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

                // -----get profession data-------

                $keyword = "Pekerjaan";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });
                $pattern = "/(?<=kerjaan ).*/i";
                $pekerjaan = $sentences[0];
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

                // -----get citizenship data-------
                $keyword = "Kewarganegaraan";
                usort($sentences, function ($a, $b) use ($keyword) {
                    return compare_first_words_to_keyword($a, $b, $keyword);
                });
                $pattern = "/(?<=negaraan).*/i";
                $kewarganegaraan = $sentences[0];
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

                //result
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


        $image->text('PROPERTI INI MILIK FAR CAPITAL', $ukuran_lebar, $ukuran_tinggi, function ($font) {
            $font->file(public_path("Roboto-Black.ttf"));
            $font->size(40);
            $font->color([255, 255, 255, 0.3]);
            $font->align('center');
            $font->valign('top');
        });
        $image->text('PROPERTI INI MILIK FAR CAPITAL', $ukuran_lebar2, $ukuran_tinggi2, function ($font) {
            $font->file(public_path("Roboto-Black.ttf"));
            $font->size(40);
            $font->color([255, 255, 255, 0.3]);
            $font->align('center');
            $font->valign('top');
        });
        $image->text('PROPERTI INI MILIK FAR CAPITAL', $ukuran_lebar3, $ukuran_tinggi3, function ($font) {
            $font->file(public_path("Roboto-Black.ttf"));
            $font->size(40);
            $font->color([255, 255, 255, 0.3]);
            $font->align('center');
            $font->valign('top');
        });
        $image->encode('jpg');

        if (!$identity) {
            if ($nik) {
                return response()->json([
                    "status" => false,
                    "message" => "NIK sudah terdaftar di email atau akun lain.",
                    "data" => 'nik'
                ]);
            }
            Storage::disk('public')->put('images/' . $image_name, $image);
            $payload["ktp"] =  'images/' . $image_name;
            $identity = Identity::create($payload);
        } else {
            if ($payload['nik'] != $identity['nik']) {
                return response()->json([
                    "status" => false,
                    "message" => "NIK tidak boleh berubah",
                    "data" => 'nik'
                ]);
            }

            if ($request->hasFile("ktp")) {
                Storage::disk('public')->delete($identity->ktp);

                Storage::disk('public')->put('images/' . $image_name, $image);
                $payload["ktp"] = 'images/' . $image_name;
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
