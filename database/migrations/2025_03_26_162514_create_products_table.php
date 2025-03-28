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
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 30)->unique();
            $table->string('code', 30)->unique()->nullable();
            $table->decimal('selling_price', 24, 6)->default(0);
            $table->integer('alert_quantity')->default(0);
            $table->foreignId('unit_id')->constrained()->onDelete('cascade'); 
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); 
            $table->integer('quantity_in_stock')->default(0);
            $table->decimal('purchase_cost', 24, 6)->default(0);
            $table->decimal('cost_price', 24, 6)->default(0);
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
