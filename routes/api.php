<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductAdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSellerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'registrasi'])->name('register');

Route::middleware(['auth:api'])->group(
    function () {
        Route::get('/products', [ProductController::class, 'index'])->name('product.index');
        Route::post('/products/checkout', [ProductController::class, 'checkout'])->name('product.checkout');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('product.show');

        Route::get('/admin/products', [ProductAdminController::class, 'index'])->name('adminProduct.index');
        Route::get('/admin/products/{id}', [ProductAdminController::class, 'show'])->name('adminProduct.show');
        Route::post('/admin/products', [ProductAdminController::class, 'store'])->name('adminProduct.store');
        Route::put('/admin/products/{id}', [ProductAdminController::class, 'update'])->name('adminProduct.update');
        Route::delete('/admin/products/{id}', [ProductAdminController::class, 'destroy'])->name('adminProduct.destroy');

        Route::get('/seller/products', [ProductSellerController::class, 'index'])->name('sellerProduct.index');
        Route::get('/seller/products/{id}', [ProductSellerController::class, 'show'])->name('sellerProduct.show');
        Route::post('/seller/products', [ProductSellerController::class, 'store'])->name('sellerProduct.store');
        Route::put('/seller/products/{id}', [ProductSellerController::class, 'update'])->name('sellerProduct.update');
        Route::delete('/seller/products/{id}', [ProductSellerController::class, 'destroy'])->name('sellerProduct.destroy');

        Route::post('/add/owner', [AuthController::class, 'addSeller'])->name('user.addSeller');

        Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
        Route::get('/categories/{id}', [CategoriesController::class, 'show'])->name('categories.show');
        Route::delete('/categories/{id}', [CategoriesController::class, 'destroy'])->name('categories.destroy');
        Route::put('/categories/{id}', [CategoriesController::class, 'update'])->name('categories.update');

        Route::post('/carts', [CartController::class, 'store'])->name('carts.store');
        Route::get('/carts', [CartController::class, 'index'])->name('carts.index');
        Route::post('/carts/checkout', [CartController::class, 'checkout'])->name('carts.checkout');
        Route::get('/carts/{id}', [CartController::class, 'show'])->name('carts.show');
        Route::put('/carts/{id}', [CartController::class, 'update'])->name('carts.update');
        Route::delete('/carts/{id}', [CartController::class, 'destroy'])->name('carts.destroy');

    }
);
