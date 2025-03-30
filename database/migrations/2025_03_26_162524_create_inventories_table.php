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
        Schema::create('inventories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->enum('movement_type', ['entry', 'exit']);
            $table->decimal('stock_before', 10, 2);
            $table->decimal('stock_after', 10, 2);
            $table->string('reference_type')->nullable(); // 'App\Models\Invoice', 'App\Models\PurchaseOrder', etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID de la facture/commande
    $table->timestamps();

    $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
