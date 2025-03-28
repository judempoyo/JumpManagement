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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_entry_id'); // Lien vers la dette/créance
            $table->decimal('amount', 24, 6); // Montant du paiement
            $table->date('payment_date'); // Date du paiement
            $table->string('payment_method'); // Espèces, chèque, virement, etc.
            $table->string('reference')->nullable(); // Référence du paiement
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id'); // Utilisateur qui a enregistré le paiement
            $table->timestamps();
        
            $table->foreign('financial_entry_id')->references('id')->on('financial_entries');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
