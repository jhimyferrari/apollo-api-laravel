<?php

use App\Http\Controllers\api\v1\Auth\LoginController as LoginControllerV1;
use App\Http\Controllers\api\v1\OrganizationController as OrganizationControllerV1;
use App\Http\Controllers\api\v1\UserController as UserControllerV1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')
    ->name('v1.')
    ->group(callback: function () {

        Route::post('/login', [LoginControllerV1::class, 'login'])->name('login');

        Route::group(['as' => 'organizations.'], function () {
            Route::post('/organizations', [OrganizationControllerV1::class, 'store'])->name('store');
        });

        Route::middleware('auth')->group(function () {
            Route::group(['as' => 'users.'], function () {
                Route::post('/users', [UserControllerV1::class, 'store'])->name('store');
            });

        });
    });
