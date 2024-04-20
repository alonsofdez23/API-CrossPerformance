<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/register', [UserController::class, 'createUser']);
Route::post('/auth/login', [UserController::class, 'loginUser']);

Route::get('/token', [UserController::class, 'testToken'])->middleware('auth:sanctum');

// Revoca tokens del usuario
Route::post('/auth/logout', function (Request $request) {
    $user = $request->auth();

    // Revoca todo los tokens...
    $user->tokens()->delete();

    // Revoca el token que ha sido usado para autentificar la petición actual...
    // $request->user()->currentAccessToken()->delete();

    // Revoke a specific token...
    // $user->tokens()->where('id', $tokenId)->delete();

    return response()->json(['message' => 'Token de sesión revocado'], 200);
})->middleware('auth:sanctum');
