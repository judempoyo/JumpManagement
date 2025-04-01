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
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('customer_id')->constrained()->nullable()->onDelete('cascade'); 
            $table->string('customer_name')->nullable();
            $table->date('date');
            $table->time('time');
            $table->decimal('total', 24, 6)->default(0);
            $table->decimal('amount_payable', 24, 6)->default(0);
            $table->decimal('discount', 24, 6)->default(0);
            $table->boolean('status')->default(false);
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            $table->boolean('paid')->default(false);
            $table->boolean('delivered')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
