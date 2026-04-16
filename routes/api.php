<?php

use App\Http\Controllers\api\v1\Auth\LoginController as LoginControllerV1;
use App\Http\Controllers\api\v1\ClientController as ClientControllerV1;
use App\Http\Controllers\api\v1\OrganizationController as OrganizationControllerV1;
use App\Http\Controllers\api\v1\UserController as UserControllerV1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')
    ->name('v1.')
    ->group(callback: function () {

        // Non authenticated routes
        Route::group(['as' => 'organizations.', 'prefix' => '/organizations'], function () {
            Route::post('/', [OrganizationControllerV1::class, 'store'])->name('store');
        });

        Route::post('/login', [LoginControllerV1::class, 'login'])->name('login');

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::group(['as' => 'users.', 'prefix' => '/users'], function () {
                Route::post('/', [UserControllerV1::class, 'store'])->name('store');
                Route::get('/', [UserControllerV1::class, 'index'])->name('index');
                Route::get('/{user}', [UserControllerV1::class, 'show'])->name('show');
                Route::delete('/{user}', [UserControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{user}', [UserControllerV1::class, 'update'])->name('update');
            });
            Route::group(['as' => 'clients.', 'prefix' => '/clients'], function () {
                Route::post('/', [ClientControllerV1::class, 'store'])->name('store');
                Route::get('/', [ClientControllerV1::class, 'index'])->name('index');
                Route::get('/{client}', [ClientControllerV1::class, 'show'])->name('show');
                Route::delete('/{client}', [ClientControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{client}', [ClientControllerV1::class, 'update'])->name('update');
            });

        });
    });
