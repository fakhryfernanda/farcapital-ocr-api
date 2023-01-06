<?php

use App\Http\Controllers\IdcardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ----------( identity )---------
Route::post("/identity/add", [IdcardController::class, "store"]);
Route::post("/upload", [IdcardController::class, "readImage"]);

// ----------( user )---------
Route::get("/user", [UserController::class, "index"]);
Route::get("/user/{id}", [UserController::class, "show"]);
Route::post("/user/add", [UserController::class, "store"]);
Route::post("/user/{id}/edit", [UserController::class, "update"]);
Route::post("/user/{id}/delete", [UserController::class, "destroy"]);

// ----------( role )---------
Route::get("/role", [RoleController::class, "index"]);

// ---------{Sanctum}-------
Route::post("/login", [AuthController::class, "login"]);
Route::get("/me", [AuthController::class, "getUser"])->middleware("auth:sanctum");
