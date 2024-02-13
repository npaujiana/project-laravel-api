<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductController;
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
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('product.show');
        Route::post('/products', [ProductController::class, 'store'])->name('product.store');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('product.update');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('product.destroy');

        Route::post('/add/owner', [AuthController::class, 'addSeller'])->name('user.addSeller');

        Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
        Route::get('/categories/{id}', [CategoriesController::class, 'show'])->name('categories.show');
        Route::delete('/categories/{id}', [CategoriesController::class, 'destroy'])->name('categories.destroy');
        Route::put('/categories/{id}', [CategoriesController::class, 'update'])->name('categories.update');
    }
);
