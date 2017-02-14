<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShoppingCartTable extends Migration {
    public function up() {
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('content');
        });
    }

    public function down() {
        Schema::drop('shopping_cart');
    }
}
