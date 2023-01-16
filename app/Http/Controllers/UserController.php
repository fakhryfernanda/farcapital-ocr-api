<?php

namespace App\Http\Controllers;

use App\Mail\ResetPassword;
use App\Models\Password_resets;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class UserController extends Controller
{
    function index()
    {
        $user = User::query()
            ->join('role', 'users.id_role', '=', 'role.id')
            ->select('users.*', 'role.nama_role')
            ->orderBy('users.id_role', 'asc')
            ->get();

        // dd($user);
        return response()->json([
            "status" => true,
            "message" => "list user",
            "data" => $user
        ]);
    }

    //----------(batas suci)----------
    function show($id)
    {
        $user = User::query()
            ->join('role', 'users.id_role', '=', 'role.id')
            ->select('users.*', 'role.nama_role')
            ->where("users.id", $id)
            ->first();

        if (!isset($user)) {
            return response()->json([
                "status" => false,
                "message" => "data tidak ditemukan",
                "data" => null
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "data user",
            "data" => $user
        ]);
    }

    //----------(batas suci)----------
    function store(Request $request)
    {
        $token = substr(sha1(time()), 0, 16);
        $payload = $request->all();
        $payload['token'] = $token;
        if (!isset($payload['email'])) {
            return response()->json([
                "status" => false,
                "message" => "email belum diisi",
                "data" => 'email'
            ]);
        }
        if (!isset($payload['password'])) {
            return response()->json([
                "status" => false,
                "message" => "password belum diisi",
                "data" => 'password'
            ]);
        }

        $count = User::where('email', '=', $payload['email'])->count();

        if ($count > 0) {
            return response()->json([
                "status" => false,
                "message" => "email sudah terdaftar",
                "data" => 'email'
            ]);
        }

        $user = User::query()->create($payload);
        
        return response()->json([
            "status" => true,
            "message" => "Akun " . $user['email'] . " berhasil dibuat, silahkan konfirmasi melalui email anda",
            "data" => $user
        ]);
    }
    //----------(batas suci)----------
    function emailRegist($token)
    {
        $user = user::query()
            ->where("token", $token)
            ->first();

        if (!isset($user)) {
            return response()->json([
                "status" => false,
                "message" => "data tidak ditemukan",
                "data" => null
            ]);
        }
        if($user['updated_at']->addHour(1) < now()){
            return response()->json([
                "status" => false,
                "message" => "token kadaluarsa",
                "data" => null
            ]);
        }

        $payload = [
            'valid' => 1,
            'token' => null
        ];
        $user->fill($payload);
        $user->save();

        return response()->json([
            "status" => true,
            "message" => "berhasil registrasi akun",
            "data" => $user
        ]);
    }

    //----------(batas suci)----------
    function update(Request $request, $id)
    {
        $user = User::query()->where("id", $id)->first();
        if (!isset($user)) {
            return response()->json([
                "status" => false,
                "message" => "data tidak ditemukan",
                "data" => null
            ]);
        }

        $payload = $request->all();

        $user->fill($payload);
        $user->save();

        return response()->json([
            "status" => true,
            "message" => "perubahan data tersimpan",
            "data" => $user
        ]);
    }

    //----------(batas suci)----------
    function getEmailby($token)
    {
        $passreset = Password_resets::query()
            ->where("token", $token)
            ->first();

        if (!isset($passreset)) {
            return response()->json([
                "status" => false,
                "message" => "data tidak ditemukan",
                "data" => null
            ]);
        }
        if($passreset['updated_at']->addHour(1) < now()){
            return response()->json([
                "status" => false,
                "message" => "token kadaluarsa",
                "data" => null
            ]);
        }
        return response()->json([
            "status" => true,
            "message" => "data user",
            "data" => $passreset
        ]);
    }

    //----------(batas suci)----------
    function reset(Request $request)
    {
        $email = $request->input('email');
        $link = $request->input('link');
        $from = $request->input('from');
        $target = $request->input('target');
        $token = substr(sha1(time()), 0, 16);
        $user = User::query()->where("email", $email)->first();
        if (!isset($user)) {
            return response()->json([
                "status" => false,
                "message" => "email tidak terdaftar",
                "data" => null
            ]);
        }
        $payload = [
            "email" => $email,
            "token" => $token
        ];
        $from = $link.'/'.$from;
        $target = $link.'/'.$target.'/'.$token;

        $link = [
            'from' => $from,
            'target' => $target,
        ];

        $count = Password_resets::where('email', '=', $email)->count();

        if ($count == 0) {
            Password_resets::query()->create($payload);
        }else{
            Password_resets::query()->where('email', $email)->update($payload);
        }
        Mail::to($email)->send(new ResetPassword($link));

        return response()->json([
            "status" => true,
            "message" => "link terkirim ke ". $email,
            "data" => [
                'target' => $target
                ]
        ]);
    }
    //----------(batas suci)----------
    function changeforgotpass(Request $request){
        $token = $request->input('token');
        $email = $request->input('email');

        $passreset = Password_resets::query()
            ->where("token", $token)
            ->first();

        $user = User::query()
            ->where("email", $email)
            ->first();

        if (!isset($passreset)) {
            return response()->json([
                "status" => false,
                "message" => "token tidak ditemukan",
                "data" => null
            ]);
        }

        if (!isset($user)) {
            return response()->json([
                "status" => false,
                "message" => "user tidak ditemukan",
                "data" => null
            ]);
        }

        $payload = [
            'password'=> $request->input('password')
        ];

        $user->fill($payload);
        $user->save();

        Password_resets::query()->where('token', $token)->delete();

        return response()->json([
            "status" => true,
            "message" => "perubahan data tersimpan",
            "data" => $user
        ]);




    }

    //----------(batas suci)----------
    function destroy($id)
    {
        $user = User::query()->where("id", $id)->first();
        if (!isset($user)) {
            return response()->json([
                "status" => false,
                "message" => "data tidak ditemukan",
                "data" => null
            ]);
        }

        $user->delete();

        return response()->json([
            "status" => true,
            "message" => "Data Terhapus",
            "data" => $user
        ]);
    }
}
