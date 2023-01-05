<?php

use App\Http\Controllers\IdcardController;
use App\Http\Controllers\AuthController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

<<<<<<< HEAD

Route::post("/upload", [IdcardController::class, "readImage"]);
=======
// ---------{Sanctum}-------
Route::post("/login", [AuthController::class, "login"]);
Route::get("/me", [AuthController::class, "getUser"])->middleware("auth:sanctum");
>>>>>>> 701e6e9bcefa315a65b1b9a88994a9b12365e3a6
