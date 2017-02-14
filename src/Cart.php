<?php
namespace Bkremenovic\Shoppingcart;

use Closure;
use DB;

use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;

use Bkremenovic\Shoppingcart\Exceptions\ItemNotFoundException;
use Bkremenovic\Shoppingcart\Exceptions\CartNotFoundException;

class Cart {
    private $session;
    private $events;

    private $instance = "default";

    public function __construct(SessionManager $session, Dispatcher $events) {
        $this->session = $session;
        $this->events = $events;
    }

    public function instance($instance = null) {
        if($instance) {
            $this->instance = $instance;
        }

        return $this;
    }

    private function getInstance() {
        return sprintf('%s.%s', 'cart', $this->instance);
    }

    public function content() {
        if($this->session->has($this->getInstance())) {
            return $this->session->get($this->getInstance());
        } else {
            return new Collection([]);
        }
    }

    public function destroy() {
        $this->session->remove($this->getInstance());
    }

    public function get($hash) {
        $cart = $this->content(); 

        if(!$cart->has($hash)) {
            throw new ItemNotFoundException("This cart instance does not contain item {$hash}.");
        }

        return $cart->get($hash);
    }

    public function remove($hash) {
        $cart = $this->content(); 

        if(!$cart->has($hash)) {
            throw new ItemNotFoundException("This cart instance does not contain item {$hash}.");
        }

        $cart->forget($hash);

        $this->session->put($this->getInstance(), $cart);
    }

    public function update($hash, $quantity) {
        $cart = $this->content(); 

        if(!$cart->has($hash)) {
            throw new ItemNotFoundException("This cart instance does not contain item {$hash}.");
        }

        $item = $cart->get($hash);

        $item->quantity = $quantity;

        if($item->quantity >= 1) {
            $cart->put($item->hash, $item);
        } else {
            $cart->forget($item->hash);
        }

        $this->session->put($this->getInstance(), $cart);
    }

    public function add($model, $quantity = 1, $options = []) {
        $cart = $this->content(); 
        $item = new CartItem($model, $options);

        if($cart->has($item->hash)) {
            $item = $cart->get($item->hash);
        }
        $item->quantity += $quantity;

        $cart->put($item->hash, $item);
        $this->session->put($this->getInstance(), $cart);
    }

    public function count() {
        $cart = $this->content();

        return $cart->sum('quantity');
    }

    public function __get($attribute) {
        if(property_exists($this, $attribute)) {
            return $this->{$attribute};
        }
        
        if($attribute === 'subtotal') {
            $content = $this->content();
        
            $subtotal = $content->reduce(function ($subtotal, CartItem $item) {
                return $subtotal + ($item->quantity * $item->price);
            }, 0);

            return $subtotal;
        }

        if($attribute === 'tax') {
            $content = $this->content();
        
            $tax = $content->reduce(function ($tax, CartItem $item) {
                return $tax + ($item->quantity * $item->tax);
            }, 0);

            return $tax;
        }

        if($attribute === 'total') {
            $content = $this->content();

            $total = $content->reduce(function ($total, CartItem $item) {
                return $total + ($item->quantity * $item->priceTax);
            }, 0);

            return $total;
        }
        
        return null;
    }

    public function subtotal() {
        return $this->subtotal;
    }

    public function tax() {
        return $this->tax;
    }

    public function total() {
        return $this->total;
    }
    
    private function getTableName() {
        return config('cart.table', 'shopping_cart');
    }

    public function store() {
        $store = DB::table($this->getTableName())->insertGetId([
            'content' => serialize($this->content())
        ]);

        return $store;
    }

    public function restore($cart_id, $store = false) {
        $cart = DB::table($this->getTableName())->find($cart_id);

        if($cart) {
            $cart = unserialize($cart->content);

            if($store) {
                $this->session->put($this->getInstance(), $cart);
            }

            return $cart;
        } else {
            throw new CartNotFoundException("Cart id = {$cart_id} does not exist in the database.");
        }
    }
}