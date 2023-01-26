<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    function login(Request $request)
    {
        $user = User::query()
            ->where("email", $request->input("email"))
            ->first();
        
        // cek user berdasarkan email (availability user)
        if ($user == null) {
            return response()->json([
                "status" => false,
                "message" => "Email tidak ditemukan",
                "data" => 'email'
            ]);
        }

        // cek password
        if (!Hash::check($request->input("password"), $user->password)) {
            return response()->json([
                "status" => false,
                "message" => "Password salah",
                "data" => 'password'
            ]);
        }

        if($user['valid'] == 0){
            return response()->json([
                "status" => false,
                "message" => "Akun belum tervalidasi",
                "data" => 'invalid'
            ]);
        }

        // buat token untuk authorisasi
        $token = $user->createToken("auth_token");
        return response()->json([
            "status" => true,
            "message" => "token berhasil dibuat",
            "data" => [
                "auth" => [
                    "token" => $token->plainTextToken,
                    "token_type" => 'Bearer'
                ],
                "user" => $user
            ]
        ]);
    }

    function getUser(Request $request)
    {
        $user = $request->user();
        return response()->json([
            "status" => true,
            "message" => "data user",
            "data" => $user
        ]);
    }

    function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return response()->json([
            "status" => true,
            "message" => "Sukses Logout",
            "data" => $user
        ]);
    }
}