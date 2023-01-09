<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
        $payload = $request->all();
        if (!isset($payload['email'])) {
            return response()->json([
                "status" => false,
                "message" => "email belum diisi",
                "data" => null
            ]);
        }
        if (!isset($payload['password'])) {
            return response()->json([
                "status" => false,
                "message" => "password belum diisi",
                "data" => null
            ]);
        }
        // dd($payload);
        $count = User::where('email', '=', $payload['email'])->count();

        if ($count > 0) {
            return response()->json([
                "status" => false,
                "message" => "email sudah terdaftar",
                "data" => 'email'
            ]);
        }

        $user = User::query()->create($payload);
        // dd($user);
        return response()->json([
            "status" => true,
            "message" => "Akun " . $user['email'] . " berhasil dibuat",
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
