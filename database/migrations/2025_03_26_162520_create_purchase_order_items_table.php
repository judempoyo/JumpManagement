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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 24, 6)->default(0);
            $table->decimal('subtotal', 24, 6)->default(0);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->timestamps();
        
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
