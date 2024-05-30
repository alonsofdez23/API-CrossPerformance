<?php

use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\EntrenoController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/base64', [UserController::class, 'testBase64']);

Route::post('/auth/register', [UserController::class, 'createUser']);
Route::post('/auth/login', [UserController::class, 'loginUser']);

Route::group(['middleware' => ["auth:sanctum"]], function () {
    Route::get('/auth/user', [UserController::class, 'user']);
    Route::get('/auth/token', [UserController::class, 'testToken']);
    Route::get('/auth/logout', [UserController::class, 'logout']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::patch('users/avatar/{user}', [UserController::class, 'updateAvatar']);

    // Pagos
    Route::post('/create-customer', [PaymentController::class, 'createCustomer']);
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
    Route::post('/pago-unico', [PaymentController::class, 'pagoUnico']);

    // Suscripciones
    Route::post('/pagos/suscripcion/create', [PaymentController::class, 'subscribe']);
    Route::post('/pagos/suscripcion/cancel', [PaymentController::class, 'cancel']);
    Route::post('/pagos/suscripcion/change', [PaymentController::class, 'changeSubscribe']);
    Route::post('/pagos/suscripcion/resume', [PaymentController::class, 'resumeSubscribe']);

    // Suscripciones custom
    Route::post('/pagos/suscripcion/custom', [PaymentController::class, 'customSubscribe']);

    // Route::post(
    //     '/stripe/webhook',
    //     '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook'
    // );

    // Entrenos
    Route::get('/entrenos', [EntrenoController::class, 'index']);
    Route::get('/entrenos/{entreno}', [EntrenoController::class, 'show']);

    // Clases
    Route::get('/clases', [ClaseController::class, 'index']);
    Route::get('/clases/date/{date}', [ClaseController::class, 'indexDate']);
    Route::get('/clases/{clase}', [ClaseController::class, 'show']);

    //Atletas a clases
    Route::post('clases/join/{clase}', [ClaseController::class, 'join']);
    Route::post('clases/leave/{clase}', [ClaseController::class, 'leave']);
    Route::post('clases/joinatleta/{atleta}/clase/{clase}', [ClaseController::class, 'joinAtleta']);
    Route::post('clases/leaveatleta/{atleta}/clase/{clase}', [ClaseController::class, 'leaveAtleta']);

    Route::group(['middleware' => ['role:admin|coach']], function() {
        // Users
        Route::get('/users/role/{role}', [UserController::class, 'usersForRole']);
        Route::get('/users/roles/admincoach', [UserController::class, 'usersAdminCoach']);
        Route::delete('/user/{user}', [UserController::class, 'destroy']);
        // Roles
        Route::post('/user/{user}/role/{role}', [RoleController::class, 'assignRole']);
        Route::post('/user/{user}/revokerole', [RoleController::class, 'revokeRole']);

        // Entrenos
        Route::post('/entrenos', [EntrenoController::class, 'store']);
        Route::put('/entrenos/{entreno}', [EntrenoController::class, 'update']);
        Route::delete('/entrenos/{entreno}', [EntrenoController::class, 'destroy']);

        // Clases
        Route::post('/clases', [ClaseController::class, 'store']);
        Route::put('/clases/{clase}', [ClaseController::class, 'update']);
        Route::delete('/clases/{clase}', [ClaseController::class, 'destroy']);
        Route::delete('/clasesmail/{clase}', [ClaseController::class, 'destroyMail']);

        // Entrenos en clases
        Route::post('clases/add/{clase}', [ClaseController::class, 'addEntrenoUpdate']);
        Route::post('clases/delete/{clase}', [ClaseController::class, 'deleteEntrenoUpdate']);
    });

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
