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
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['debt', 'receivable']); // Type d'entrée
            $table->decimal('total_amount', 24, 6); // Montant total
            $table->decimal('remaining_amount', 24, 6); // Reste à payer
            $table->date('start_date'); // Date de création
            $table->date('due_date')->nullable(); // Échéance
            $table->boolean('is_paid')->default(false);
            
            // Relation avec les documents (facture ou bon de commande)
            $table->unsignedBigInteger('source_document_id');
            $table->string('source_document_type'); // 'App\Models\Invoice' ou 'App\Models\PurchaseOrder'
            
            // Relation avec le partenaire (client ou fournisseur)
            $table->unsignedBigInteger('partner_id');
            $table->string('partner_type'); // 'App\Models\Customer' ou 'App\Models\Supplier'
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
    }
};
