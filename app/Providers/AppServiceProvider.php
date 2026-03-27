<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        Paginator::useBootstrap();

        View::composer('layouts.app', function ($view) {
            try {
                $cartCount = 0;

                // Kiểm tra session có tồn tại không
                if (session()->has('cart')) {
                    $cartItems = session('cart', []);
                    $cartCount = is_array($cartItems) ? count($cartItems) : 0;
                }

                // Hoặc nếu dùng database
                if (Auth::check()) {
                    $cart = Cart::where('user_id', Auth::id())->first();
                    if ($cart) {
                        $cartCount = $cart->cartItems()->sum('quantity');
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error in cart composer: ' . $e->getMessage());
                $cartCount = 0;
            }

            $view->with('cart_count', $cartCount);
        });
    }
}
