<?php

use App\Http\Controllers\Admin\CashbackAdminController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductsController;
use App\Http\Controllers\Admin\UserController;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

// Config routes
Route::group([
    'middleware' => ['auth', 'admin']
], function () {
    // Route::get('configs', [ConfigController::class, 'edit'])->name('admin.configs.edit');
    // Route::post('configs', [ConfigController::class, 'update'])->name('admin.configs.update');

    //categories
    Route::group(['prefix' => 'categories'], function () {
        Route::get('/', [CategoryController::class, 'index'])->name('admin.categories.index');
        Route::get('/create', [CategoryController::class, 'create'])->name('admin.categories.create');
        Route::post('/store', [CategoryController::class, 'store'])->name('admin.categories.store');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('admin.categories.edit');
        Route::put('/update/{id}', [CategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
        Route::post('/toggle-status/{id}', [CategoryController::class, 'toggleStatus'])->name('admin.categories.toggleStatus');
        Route::get('/{id}', [CategoryController::class, 'show'])->name('admin.categories.show');
    });
    // Route group cho Products
    Route::group(['prefix' => 'products'], function () {
        Route::get('/', [ProductsController::class, 'index'])->name('admin.products.index');
        Route::get('/create', [ProductsController::class, 'create'])->name('admin.products.create');
        Route::post('/store', [ProductsController::class, 'store'])->name('admin.products.store');
        Route::get('/edit/{id}', [ProductsController::class, 'edit'])->name('admin.products.edit');
        Route::put('/update/{id}', [ProductsController::class, 'update'])->name('admin.products.update');
        Route::delete('/{id}', [ProductsController::class, 'destroy'])->name('admin.products.destroy');
        Route::post('/toggle-status/{id}', [ProductsController::class, 'toggleStatus'])->name('admin.products.toggleStatus');
        Route::get('/{id}', [ProductsController::class, 'show'])->name('admin.products.show');
    });
    // Quản lý khách hàng
    Route::group(['prefix' => 'customers'], function () {
        Route::get('/', [CustomerController::class, 'index'])->name('admin.customers.index');
        Route::get('/create', [CustomerController::class, 'create'])->name('admin.customers.create');
        Route::post('/store', [CustomerController::class, 'store'])->name('admin.customers.store');
        Route::get('/edit/{id}', [CustomerController::class, 'edit'])->name('admin.customers.edit');
        Route::put('/update/{id}', [CustomerController::class, 'update'])->name('admin.customers.update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');
        Route::post('/toggle-status/{id}', [CustomerController::class, 'toggleStatus'])->name('admin.customers.toggleStatus');
        Route::post('/adjust-balance/{id}', [CustomerController::class, 'adjustBalance'])->name('admin.customers.adjustBalance');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('admin.customers.show');
    });

    // Quản lý cấu hình thanh toán
    Route::group(['prefix' => 'configs'], function () {
        Route::get('/payment', [ConfigController::class, 'paymentSettings'])->name('admin.configs.payment');
        Route::post('/payment', [ConfigController::class, 'updatePaymentSettings'])->name('admin.configs.updatePayment');
    });

    Route::group(['prefix' => 'deposits'], function () {
        Route::get('/', [DepositController::class, 'index'])->name('deposits.index');
        Route::get('/{id}', [DepositController::class, 'show'])->name('deposits.show');
        Route::post('/{id}/approve', [DepositController::class, 'approve'])->name('deposits.approve');
        Route::post('/{id}/reject', [DepositController::class, 'reject'])->name('deposits.reject');
    });

    // Payment management routes
    Route::group(['prefix' => 'payments'], function () {
        Route::get('/', [PaymentController::class, 'index'])->name('admin.payments.index');
        Route::post('/{id}/approve', [PaymentController::class, 'approve'])->name('admin.payments.approve');
        Route::post('/{id}/reject', [PaymentController::class, 'reject'])->name('admin.payments.reject');
    });

    // Thêm routes quản lý hoàn tiền
    Route::group(['prefix' => 'cashback'], function () {
        Route::get('/', [CashbackAdminController::class, 'index'])->name('cashback.index');
        Route::post('/{id}/approve', [CashbackAdminController::class, 'approve'])->name('cashback.approve');
        Route::post('/{id}/reject', [CashbackAdminController::class, 'reject'])->name('cashback.reject');
        Route::post('/{id}/process', [CashbackAdminController::class, 'markProcessed'])->name('cashback.process');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'index'])->name('user.index');
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        // BỎ "/users" thừa trong route reset-password
    });
});
