<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ParcelController;
use App\Http\Controllers\Api\ReceiverController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// User route with its own middleware
Route::get('/user', [UserController::class, 'me'])
    ->middleware('auth:sanctum')
    ->name('api.user.me');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    
    // Client routes
    Route::get('/clients', [ClientController::class, 'index'])->name('api.clients.index');
    Route::get('/clients/search', [ClientController::class, 'search'])->name('api.clients.search');
    Route::post('/clients/add', [ClientController::class, 'store'])->name('api.clients.store');
    Route::get('/clients/{id}', [ClientController::class, 'show'])->name('api.clients.show');
    Route::post('/clients/{client_id}/edit', [ClientController::class, 'update'])->name('api.clients.update');
    Route::post('/clients/{client_id}/retoken', [ClientController::class, 'regenerateToken'])->name('api.clients.retoken');
    Route::delete('/clients/{id}', [ClientController::class, 'destroy'])->name('api.clients.destroy');

    // Parcel routes
    Route::get('/parcels', [ParcelController::class, 'index'])->name('api.parcels.index');
    Route::post('/parcels', [ParcelController::class, 'store'])->name('api.parcels.store');
    Route::get('/parcels/{id}', [ParcelController::class, 'show'])->name('api.parcels.show');
    Route::put('/parcels/{id}', [ParcelController::class, 'update'])->name('api.parcels.update');
    Route::delete('/parcels/{id}', [ParcelController::class, 'destroy'])->name('api.parcels.destroy');
    Route::get('/clients/{clientId}/parcels', [ParcelController::class, 'getByClient'])->name('api.parcels.by-client');

    // Receiver routes
    Route::get('/receivers', [ReceiverController::class, 'index'])->name('api.receivers.index');
    Route::post('/receivers', [ReceiverController::class, 'store'])->name('api.receivers.store');
    Route::get('/receivers/{id}', [ReceiverController::class, 'show'])->name('api.receivers.show');
    Route::put('/receivers/{id}', [ReceiverController::class, 'update'])->name('api.receivers.update');
    Route::delete('/receivers/{id}', [ReceiverController::class, 'destroy'])->name('api.receivers.destroy');

    // Item routes
    Route::get('/items', [ItemController::class, 'index'])->name('api.items.index');
    Route::post('/items', [ItemController::class, 'store'])->name('api.items.store');
    Route::get('/items/{id}', [ItemController::class, 'show'])->name('api.items.show');
    Route::put('/items/{id}', [ItemController::class, 'update'])->name('api.items.update');
    Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('api.items.destroy');
});
