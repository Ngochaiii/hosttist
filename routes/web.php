<?php

use App\Http\Controllers\Admin\LogController;
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
*/

// ===== PUBLIC ROUTES =====
Route::get('/', [HomepageController::class, 'index'])->name('homepage');
Route::get('/service/{slug}', [HomepageController::class, 'detail'])->name('service.detail');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Static Pages
Route::get('/about-us', [AboutController::class, 'index'])->name('about.index');
Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
Route::get('/price', [PricingController::class, 'index'])->name('pricing.index');

// Services & Categories
Route::group(['prefix' => 'services'], function () {
    Route::get('/', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/{slug}', [HomepageController::class, 'detail'])->name('services.detail');
});

Route::get('/category/{categorySlug}', [HomepageController::class, 'category'])->name('category.detail');

// ===== AUTHENTICATED ROUTES =====
Route::group(['middleware' => ['frontend.auth']], function () {

    // ===== CUSTOMER MANAGEMENT =====
    Route::group(['prefix' => 'customer'], function () {
        Route::get('/profile', [CustomerController::class, 'showProfile'])->name('customer.profile');
        Route::put('/profile/update', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
        Route::get('/invoices', [CustomerController::class, 'showInvoices'])->name('customer.invoices');
        Route::get('/orders', [CustomerController::class, 'showOrders'])->name('customer.orders');
        Route::get('/orders/{id}', [CustomerController::class, 'showOrderDetail'])->name('customer.order.detail');
    });

    // ===== CUSTOMER SERVICES PORTAL =====
    Route::group(['prefix' => 'customer/services'], function () {
        // Main services dashboard
        Route::get('/', [ServiceController::class, 'index'])->name('customer.services.index');

        // Service Provision routes (for services being provisioned)
        Route::group(['prefix' => 'provision'], function () {
            Route::get('/{id}', [ServiceController::class, 'showProvision'])
                ->name('customer.services.provision.show')
                ->where('id', '[0-9]+');

            Route::get('/{id}/credentials', [ServiceController::class, 'provisionCredentials'])
                ->name('customer.services.provision.credentials')
                ->where('id', '[0-9]+');
        });

        // Customer Service routes (for active services)
        Route::group(['prefix' => 'service'], function () {
            Route::get('/{id}', [ServiceController::class, 'showService'])
                ->name('customer.services.service.show')
                ->where('id', '[0-9]+');

            Route::get('/{id}/info', [ServiceController::class, 'serviceCredentials'])
                ->name('customer.services.service.credentials')
                ->where('id', '[0-9]+');

            // Service management actions
            Route::post('/{id}/renew', [ServiceController::class, 'renewService'])
                ->name('customer.services.service.renew')
                ->where('id', '[0-9]+');

            Route::post('/{id}/cancel', [ServiceController::class, 'requestCancellation'])
                ->name('customer.services.service.cancel')
                ->where('id', '[0-9]+');
        });
        Route::get('/provision/{id}/ssl/{type}', [ServiceController::class, 'downloadSSL'])
            ->name('customer.services.ssl.download')
            ->where('id', '[0-9]+')
            ->where('type', 'certificate|private_key|ca_bundle|all');
    });

    // ===== WALLET & PAYMENT =====
    Route::group(['prefix' => 'wallet'], function () {
        Route::get('/deposit', [WalletController::class, 'deposit'])->name('deposit');
        Route::post('/deposit/process', [WalletController::class, 'processDeposit'])->name('deposit.process');
        Route::get('/deposit/success/{code}', [WalletController::class, 'depositSuccess'])->name('deposit.success');
        Route::get('/deposit/status/{code}', [WalletController::class, 'checkDepositStatus'])->name('deposit.status');
        Route::get('/language/{locale}', [WalletController::class, 'switchLanguage'])->name('language.switch');
    });

    // ===== SHOPPING CART =====
    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', [CartController::class, 'index'])->name('cart.index');
        Route::post('/add', [CartController::class, 'addToCart'])->name('cart.add');
        Route::post('/update/{itemId}', [CartController::class, 'updateItem'])->name('cart.update');
        Route::post('/remove/{itemId}', [CartController::class, 'removeItem'])->name('cart.remove');
        Route::post('/clear', [CartController::class, 'clearCart'])->name('cart.clear');
    });

    // ===== INVOICES =====
    Route::group(['prefix' => 'invoice'], function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('invoice.index');
        Route::get('/{id}/download', [InvoiceController::class, 'downloadPdf'])->name('invoice.download');
        Route::get('/{id}/payment', [InvoiceController::class, 'proceedToPayment'])->name('invoice.payment');
    });

    // ===== ORDERS =====
    Route::get('/order/{id}', [OrderController::class, 'showOrder'])->name('order.show');

    // ===== QUOTES & CHECKOUT =====
    Route::group(['prefix' => 'quote'], function () {
        Route::get('/', [InvoiceController::class, 'showQuote'])->name('quote');
        Route::get('/download', [QuoteController::class, 'downloadPdf'])->name('quote.download');
        Route::get('/email', [QuoteController::class, 'sendEmail'])->name('quote.email');
        Route::post('/email', [QuoteController::class, 'sendEmail'])->name('quote.email.post');
        Route::post('/proceed-to-payment', [InvoiceController::class, 'proceedToPayment'])->name('proceed.payment');
    });

    // ===== PAYMENT PROCESSING =====
    Route::get('/payment/process', [InvoiceController::class, 'proceedToPayment'])->name('process.payment');

    // ===== CASHBACK =====
    Route::group(['prefix' => 'cashback'], function () {
        Route::post('/register', [CashbackController::class, 'register'])->name('cashback.register');
        Route::get('/status', [CashbackController::class, 'getStatus'])->name('cashback.status');
    });

    // ===== ADMIN ROUTES =====
    Route::group(['prefix' => 'admin'], function () {
        Route::group(['prefix' => 'logs'], function () {
            Route::get('/', [LogController::class, 'index'])->name('admin.logs.index');
            Route::get('/{id}', [LogController::class, 'show'])->name('admin.logs.show');
        });
    });
});
