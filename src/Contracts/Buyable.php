<?php
    namespace Bkremenovic\Shoppingcart\Contracts;

    interface Buyable {
        public function getBuyableID();
        public function getBuyableName();
        public function getBuyablePrice();
    }