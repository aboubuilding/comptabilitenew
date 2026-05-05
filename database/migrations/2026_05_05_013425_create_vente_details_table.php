<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vente_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vente_id')->constrained('ventes')->onDelete('cascade');
    $table->foreignId('produit_id')->constrained('produits');
    $table->float('quantite');
    $table->decimal('prix_unitaire', 12, 2);
    $table->decimal('remise', 5, 2)->default(0); // pourcentage
    $table->decimal('montant_ht', 12, 2)->storedAs('quantite * prix_unitaire * (1 - remise/100)');
    $table->decimal('montant_ttc', 12, 2)->storedAs('quantite * prix_unitaire * (1 - remise/100)'); // TVA à gérer plus tard
    $table->foreignId('inscription_id')->nullable()->constrained('inscriptions'); // si l'acheteur est un élève
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vente_details');
    }
};
