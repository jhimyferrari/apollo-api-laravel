<?php

use App\Http\Controllers\api\v1\OrganizationController as OrganizationControllerV1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')
    ->name('v1.')
    ->group(callback: function () {

        Route::group(['as' => 'organizations.'], function () {
            Route::post('/organizations', [OrganizationControllerV1::class, 'store'])->name('store');
        });

    });
