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
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->time('time');
            $table->decimal('total', 24, 6)->default(0);
            $table->decimal('amount_payable', 24, 6)->default(0);
            $table->decimal('discount', 24, 6)->default(0);
            $table->boolean('status')->default(false);
            $table->unsignedBigInteger('user_id'); // Utilisateur Laravel standard
            $table->boolean('paid')->default(false);
            $table->boolean('delivered')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('user_id')->references('id')->on('users');
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
