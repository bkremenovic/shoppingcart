<?php
namespace Bkremenovic\Shoppingcart;

use Illuminate\Contracts\Support\Arrayable;
use Bkremenovic\Shoppingcart\Contracts\Buyable;
use Bkremenovic\Shoppingcart\Exceptions\NotBuyableException;

use ReflectionClass;

class CartItem {
    public $id;
    public $name;
    public $price;
    public $options;
    public $modelName;
    
    public $hash;
    public $quantity;

    public function __construct($model, $options) {
        if(!$model instanceof Buyable) {
            throw new NotBuyableException("You must provide a model that implements Buyable interface.");
        }

        $this->id      = $model->getBuyableId();
        $this->name    = $model->getBuyableName();
        $this->price   = floatval($model->getBuyablePrice());
        $this->options = new CartItemOptions($options);
        $this->modelName   = (new ReflectionClass($model))->name;

        $this->hash = $this->generateHash($this->id, $this->modelName, $this->options->toArray());
    }

    private function generateHash($id, $model, $options) {
        ksort($options);

        return md5($id.$model.serialize($options));
    }

    private function getTaxRate() {
        return config('cart.tax', 0);
    }

    public function __get($attribute) {
        if(property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if($attribute === 'priceTax') {
            return $this->price + $this->tax;
        }
        
        if($attribute === 'subtotal') {
            return $this->quantity * $this->price;
        }
        
        if($attribute === 'total') {
            return $this->quantity * ($this->priceTax);
        }
        
        if($attribute === 'tax') {
            return $this->price * ($this->getTaxRate() / 100);
        }
        
        if($attribute === 'taxTotal') {
            return $this->tax * $this->quantity;
        }

        if($attribute === 'model') {
            return with(new $this->modelName)->find($this->id);
        }

        return null;
    }
}
