<?php
    namespace Bkremenovic\Shoppingcart;

    use Illuminate\Auth\Events\Logout;
    use Illuminate\Session\SessionManager;
    use Illuminate\Support\ServiceProvider;

    class ShoppingcartServiceProvider extends ServiceProvider {
        public function register() {
            $this->app->bind('cart', 'Bkremenovic\Shoppingcart\Cart');
            $this->mergeConfigFrom(__DIR__.'/../config/cart.php', 'cart');
            $this->publishes([__DIR__ . '/../config/cart.php' => config_path('cart.php')], 'config');

            $this->app['events']->listen(Logout::class, function() {
                $this->app->make(SessionManager::class)->forget('cart');
            });

            if (!class_exists('CreateShoppingCartTable')) {
                $timestamp = date('Y_m_d_His', time());
                $this->publishes([
                    __DIR__.'/../database/migrations/0000_00_00_000000_create_shopping_cart_table.php' => database_path('migrations/'.$timestamp.'_create_shopping_cart_table.php'),
                ], 'migrations');
            }
        }
    }