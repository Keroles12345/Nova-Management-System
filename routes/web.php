<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : app(AuthController::class)->login();
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'storeLogin'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'storeRegistration'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/customers', [CustomerManagementController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerManagementController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerManagementController::class, 'store'])->name('customers.store');
        Route::patch('/customers/{customer}', [CustomerManagementController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerManagementController::class, 'destroy'])->name('customers.destroy');

        Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
        Route::post('/orders', [OrderManagementController::class, 'store'])->name('orders.store');
        Route::patch('/orders/{order}', [OrderManagementController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [OrderManagementController::class, 'destroy'])->name('orders.destroy');
        Route::patch('/orders/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('orders.status');

        Route::get('/products', [ProductManagementController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductManagementController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductManagementController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductManagementController::class, 'destroy'])->name('products.destroy');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
});
