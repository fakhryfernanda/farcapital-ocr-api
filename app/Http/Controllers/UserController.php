<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    function index()
    {
        $role = User::query()->get();

        return response()->json([
            "status" => true,
            "message" => "list user",
            "data" => $role
        ]);
    }
}
