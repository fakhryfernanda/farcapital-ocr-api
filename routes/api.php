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

Route::get('/dashboard', [IdcardController::class, "showAll"])->middleware("auth:sanctum");

// ----------( identity )---------
Route::post("/identity/add", [IdcardController::class, "store"]);
Route::post("/upload", [IdcardController::class, "readImage"]);
Route::get("/identity/{id}", [IdcardController::class, "index"])->middleware("auth:sanctum");

// ----------( user )---------
Route::get("/user", [UserController::class, "index"])->middleware("auth:sanctum");
Route::get("/user/{id}", [UserController::class, "show"])->middleware("auth:sanctum");
Route::post("/user/forgotpass", [UserController::class, "forgotpass"]);
Route::post("/user/add", [UserController::class, "store"]);
Route::post("/user/{id}/edit", [UserController::class, "update"])->middleware("auth:sanctum");
Route::post("/user/{id}/delete", [UserController::class, "destroy"])->middleware("auth:sanctum");

// ----------( user >> validemail )---------
Route::post("/resendemailvalidation", [UserController::class, "resendEmailValidation"]);
Route::post("/emailregist/{token}", [UserController::class, "emailRegist"]);

// ----------( user >> forgetpass )---------
Route::get("/emailbytoken/{token}", [UserController::class, "getEmailby"]);
Route::post("/changeforgotpass", [UserController::class, "changeforgotpass"]);

// ----------( role )---------
Route::get("/role", [RoleController::class, "index"]);

// ---------{Sanctum}-------
Route::post("/login", [AuthController::class, "login"]);
// Route::post("/logout/{token}", [AuthController::class, "logout"]);
Route::get("/logout", [AuthController::class, "logout"])->middleware("auth:sanctum");
Route::get("/me", [AuthController::class, "getUser"])->middleware("auth:sanctum");
