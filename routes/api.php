<?php

use App\Http\Controllers\api\v1\Auth\LoginController as LoginControllerV1;
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

            });

        });
    });
