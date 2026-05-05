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
        Schema::create('carburant_vehicules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('voiture_id')->constrained('voitures')->onDelete('cascade');
    $table->date('date_plein');
    $table->decimal('quantite_litres', 10, 2);
    $table->decimal('prix_unitaire', 12, 2);
    $table->decimal('montant_total', 12, 2);
    $table->integer('kilometrage');
    $table->string('station_service')->nullable();
    $table->string('facture')->nullable();
    $table->bigInteger('annee_id')->nullable();
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
        Schema::dropIfExists('carburant_vehicules');
    }
};
