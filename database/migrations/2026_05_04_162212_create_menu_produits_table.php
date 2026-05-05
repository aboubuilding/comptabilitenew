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
        Schema::create('menu_produits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
    $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
    $table->decimal('quantite', 12, 3); // quantité utilisée pour ce menu (kg, litre, pièce)
    $table->decimal('cout_unitaire', 12, 2)->nullable(); // prix unitaire au moment de l'utilisation (historisation)
    $table->decimal('cout_total', 12, 2)->nullable(); // calculé = quantite * cout_unitaire
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
        Schema::dropIfExists('menu_produits');
    }
};
