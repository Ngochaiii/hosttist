<?php

use App\Http\Controllers\Web\HomepageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CashbackController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\PricingController;
use App\Http\Controllers\Web\QuoteController;
use App\Http\Controllers\Web\ServiceController;
use App\Http\Controllers\Web\WalletController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomepageController::class, 'index'])->name('homepage');

Route::get('/service/{slug}', [HomepageController::class, 'detail'])->name('service.detail');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::group(['prefix' => 'about-us'], function () {
    Route::get('/', [AboutController::class, 'index'])->name('about.index');
});
Route::group(['prefix' => 'contacts'], function () {
    Route::get('/', [ContactController::class, 'index'])->name('contact.index');
});
Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/{slug}', [HomepageController::class, 'detail'])->name('service.detail');
});
Route::group(['prefix' => 'price'], function () {
    Route::get('/', [PricingController::class, 'index'])->name('pricing.index');
});

Route::group(['prefix' => 'category'], function () {
    Route::get('/{categorySlug}', [HomepageController::class, 'category'])->name('category.detail');
});

// Frontend Routes (yêu cầu đăng nhập)
Route::group([
    'middleware' => ['frontend.auth']
], function () {
    // Customer Profile
    Route::group(['prefix' => 'customer'], function () {
        Route::get('/profile', [CustomerController::class, 'showProfile'])->name('customer.profile');
        Route::put('/profile/update', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
        // Thêm các routes mới
        Route::get('/invoices', [CustomerController::class, 'showInvoices'])->name('customer.invoices');
        Route::get('/orders', [CustomerController::class, 'showOrders'])->name('customer.orders');
        Route::get('/orders/{id}', [CustomerController::class, 'showOrderDetail'])->name('customer.order.detail');
    });

    // routes/web.php


Route::group(['prefix' => 'wallet'], function () {
    Route::get('/deposit', [WalletController::class, 'deposit'])->name('deposit');
    Route::post('/deposit/process', [WalletController::class, 'processDeposit'])->name('deposit.process');
    Route::get('/deposit/success/{code}', [WalletController::class, 'depositSuccess'])->name('deposit.success');
    
    // AJAX check status
    Route::get('/deposit/status/{code}', [WalletController::class, 'checkDepositStatus'])->name('deposit.status');
    
    // Language switcher
    Route::get('/language/{locale}', [WalletController::class, 'switchLanguage'])->name('language.switch');
});


    // Các routes liên quan đến giỏ hàng
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('cart.index');
        Route::post('/add', [CartController::class, 'addToCart'])->name('cart.add');
        Route::post('/update/{itemId}', [CartController::class, 'updateItem'])->name('cart.update');
        Route::post('/remove/{itemId}', [CartController::class, 'removeItem'])->name('cart.remove');
        Route::post('/clear', [CartController::class, 'clearCart'])->name('cart.clear');
    });

    // invoices
    Route::group(['prefix' => 'invoice'], function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('invoice.index');
    });

    // Báo giá và thanh toán
    Route::group(['prefix' => 'quote'], function () {
        // Hiển thị trang báo giá
        Route::get('/', [InvoiceController::class, 'showQuote'])->name('quote');

        // Thêm các routes cho báo giá
        Route::get('/download', [QuoteController::class, 'downloadPdf'])->name('quote.download');
        Route::get('/email', [QuoteController::class, 'sendEmail'])->name('quote.email');
        Route::post('/email', [QuoteController::class, 'sendEmail'])->name('quote.email.post');

        // Tiếp tục đến trang thanh toán
        Route::post('/proceed-to-payment', [InvoiceController::class, 'proceedToPayment'])->name('proceed.payment');
        // Order routes - đã có nhưng cần di chuyển ra khỏi prefix quote

        Route::get('/order/{id}', [OrderController::class, 'showOrder'])->name('order.show');

        // Invoice download route - đã có nhưng cần di chuyển ra khỏi prefix quote
        Route::get('/invoice/{id}/download', [InvoiceController::class, 'downloadPdf'])->name('invoice.download');
        // Payment routes
        Route::get('/invoice/{id}/payment', [InvoiceController::class, 'proceedToPayment'])->name('proceed.payment');
        Route::get('/payment/process', [InvoiceController::class, 'proceedToPayment'])->name('process.payment');
    });
    // Thêm routes cho hoàn tiền
    Route::group(['prefix' => 'cashback'], function () {
        Route::post('/register', [CashbackController::class, 'register'])->name('cashback.register');
        Route::get('/status', [CashbackController::class, 'getStatus'])->name('cashback.status');
    });
});
