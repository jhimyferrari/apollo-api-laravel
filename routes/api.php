<?php

use App\Http\Controllers\Api\V1\Auth\LoginController as LoginControllerV1;
use App\Http\Controllers\Api\V1\BrandController as BrandControllerV1;
use App\Http\Controllers\Api\V1\CategoryController as CategoryControllerV1;
use App\Http\Controllers\Api\V1\ClientController as ClientControllerV1;
use App\Http\Controllers\Api\V1\OrganizationController as OrganizationControllerV1;
use App\Http\Controllers\Api\V1\ProductController as ProductControllerV1;
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
                Route::post('/{client}/address', [ClientControllerV1::class, 'update'])->name('update');
                Route::get('/{client}/address', [ClientControllerV1::class, 'update'])->name('update');

                Route::post('/{client}/addresses/', [ClientControllerV1::class, 'storeAddress'])->name('addresses.store');
                Route::patch('/{client}/addresses/{address}/setDefault', [ClientControllerV1::class, 'setDefaultAddress'])->name('addresses.setDefault');
            });

            Route::group(['as' => 'sellers.', 'prefix' => '/sellers'], function () {
                Route::post('/', [SellerControllerV1::class, 'store'])->name('store');
                Route::get('/', [SellerControllerV1::class, 'index'])->name('index');
                Route::get('/{seller}', [SellerControllerV1::class, 'show'])->name('show');
                Route::delete('/{seller}', [SellerControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{seller}', [SellerControllerV1::class, 'update'])->name('update');

                Route::post('/{seller}/addresses/', [SellerControllerV1::class, 'storeAddress'])->name('addresses.store');
                Route::patch('/{seller}/addresses/{address}/setDefault', [SellerControllerV1::class, 'setDefaultAddress'])->name('addresses.setDefault');
            });

            Route::group(['as' => 'suppliers.', 'prefix' => '/suplliers'], function () {
                Route::post('/', [SupplierControllerV1::class, 'store'])->name('store');
                Route::get('/', [SupplierControllerV1::class, 'index'])->name('index');
                Route::get('/{supplier}', [SupplierControllerV1::class, 'show'])->name('show');
                Route::delete('/{supplier}', [SupplierControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{supplier}', [SupplierControllerV1::class, 'update'])->name('update');

                Route::post('/{supplier}/addresses/', [SupplierControllerV1::class, 'storeAddress'])->name('addresses.store');
                Route::patch('/{supplier}/addresses/{address}/setDefault', [SupplierControllerV1::class, 'setDefaultAddress'])->name('addresses.setDefault');

            });

            Route::group(['as' => 'brands.', 'prefix' => '/brands'], function () {
                Route::post('/', [BrandControllerV1::class, 'store'])->name('store');
                Route::get('/', [BrandControllerV1::class, 'index'])->name('index');
                Route::get('/{brand}', [BrandControllerV1::class, 'show'])->name('show');
                Route::delete('/{brand}', [BrandControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{brand}', [BrandControllerV1::class, 'update'])->name('update');
            });

            Route::group(['as' => 'categories.', 'prefix' => '/categories'], function () {
                Route::post('/', [CategoryControllerV1::class, 'store'])->name('store');
                Route::get('/', [CategoryControllerV1::class, 'index'])->name('index');
                Route::get('/{category}', [CategoryControllerV1::class, 'show'])->name('show');
                Route::delete('/{category}', [CategoryControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{category}', [CategoryControllerV1::class, 'update'])->name('update');
            });

            Route::group(['as' => 'products.', 'prefix' => '/products'], function () {
                Route::post('/', [ProductControllerV1::class, 'store'])->name('store');
                Route::get('/', [ProductControllerV1::class, 'index'])->name('index');
                Route::get('/{product}', [ProductControllerV1::class, 'show'])->name('show');
                Route::delete('/{product}', [ProductControllerV1::class, 'destroy'])->name('destroy');
                Route::patch('/{product}', [ProductControllerV1::class, 'update'])->name('update');
            });
        });
    });
