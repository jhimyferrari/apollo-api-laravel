<?php

use App\Http\Controllers\Api\V1\Auth\LoginController as LoginControllerV1;
use App\Http\Controllers\Api\V1\BrandController as BrandControllerV1;
use App\Http\Controllers\Api\V1\ClientController as ClientControllerV1;
use App\Http\Controllers\Api\V1\OrganizationController as OrganizationControllerV1;
use App\Http\Controllers\Api\V1\SellerController as SellerControllerV1;
use App\Http\Controllers\Api\V1\SupplierController as SupplierControllerV1;
use App\Http\Controllers\Api\V1\UserController as UserControllerV1;
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

            Route::group(['as' => 'sellers.', 'prefix' => '/sellers'], function () {
                Route::post('/', [SellerControllerV1::class, 'store'])->name('store');
                Route::get('/', [SellerControllerV1::class, 'index'])->name('index');
                Route::get('/{seller}', [SellerControllerV1::class, 'show'])->name('show');
                Route::delete('/{seller}', [SellerControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{seller}', [SellerControllerV1::class, 'update'])->name('update');
            });

            Route::group(['as' => 'suppliers.', 'prefix' => '/suplliers'], function () {
                Route::post('/', [SupplierControllerV1::class, 'store'])->name('store');
                Route::get('/', [SupplierControllerV1::class, 'index'])->name('index');
                Route::get('/{supplier}', [SupplierControllerV1::class, 'show'])->name('show');
                Route::delete('/{supplier}', [SupplierControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{supplier}', [SupplierControllerV1::class, 'update'])->name('update');
            });

            Route::group(['as' => 'brands.', 'prefix' => '/brands'], function () {
                Route::post('/', [BrandControllerV1::class, 'store'])->name('store');
                Route::get('/', [BrandControllerV1::class, 'index'])->name('index');
                Route::get('/{brand}', [BrandControllerV1::class, 'show'])->name('show');
                Route::delete('/{brand}', [BrandControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{brand}', [BrandControllerV1::class, 'update'])->name('update');
            });
        });
    });
