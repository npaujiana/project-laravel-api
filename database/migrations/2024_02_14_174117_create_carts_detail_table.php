<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id')->nullable(false);
            $table->foreign('cart_id')->references('id')->on('carts_header');
            $table->unsignedBigInteger('product_id')->nullable(false);
            $table->foreign('product_id')->references('id')->on('products');
            $table->double('total_price', 10, 2)->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_products');
    }
};
