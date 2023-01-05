<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    function index()
    {
        $role = Role::query()->get();

        return response()->json([
            "status" => true,
            "message" => "list role",
            "data" => $role
        ]);
    }
}
