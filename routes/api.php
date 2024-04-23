<?php

use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\EntrenoController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/register', [UserController::class, 'createUser']);
Route::post('/auth/login', [UserController::class, 'loginUser']);

Route::group(['middleware' => ["auth:sanctum"]], function () {
    Route::get('/auth/token', [UserController::class, 'testToken']);
    Route::get('/auth/logout', [UserController::class, 'logout']);

    // Entrenos
    Route::get('/entrenos', [EntrenoController::class, 'index']);
    Route::get('/entrenos/{entreno}', [EntrenoController::class, 'show']);
    Route::post('/entrenos', [EntrenoController::class, 'store']);
    Route::put('/entrenos/{entreno}', [EntrenoController::class, 'update']);
    Route::delete('/entrenos/{entreno}', [EntrenoController::class, 'destroy']);

    // Clases
    Route::get('/clases', [ClaseController::class, 'index']);
    Route::get('/clases/{clase}', [ClaseController::class, 'show']);
    Route::post('/clases', [ClaseController::class, 'store']);
    Route::put('/clases/{clase}', [ClaseController::class, 'update']);
    Route::delete('/clases/{clase}', [ClaseController::class, 'destroy']);

    //Atletas
    Route::post('clases/join/{clase}', [ClaseController::class, 'join']);
    Route::post('clases/leave/{clase}', [ClaseController::class, 'leave']);

    // Entrenos en clases
    Route::post('clases/add/{clase}', [ClaseController::class, 'addEntrenoUpdate']);
    Route::post('clases/delete/{clase}', [ClaseController::class, 'deleteEntrenoUpdate']);
});

// Revoca tokens del usuario
Route::post('/auth/logoutt', function (Request $request) {
    $user = $request->auth();

    // Revoca todo los tokens...
    $user->tokens()->delete();

    // Revoca el token que ha sido usado para autentificar la petición actual...
    // $request->user()->currentAccessToken()->delete();

    // Revoke a specific token...
    // $user->tokens()->where('id', $tokenId)->delete();

    return response()->json(['message' => 'Token de sesión revocado'], 200);
})->middleware('auth:sanctum');
