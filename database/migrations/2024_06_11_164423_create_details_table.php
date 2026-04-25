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
        Schema::create('details', function (Blueprint $table) {
            $table->id();

            $table->float('montant')->nullable();
            $table->string('libelle')->nullable();
            $table->bigInteger('paiement_id')->nullable();
            $table->tinyInteger('type_paiement')->nullable();
            $table->bigInteger('inscription_id')->nullable();
            $table->bigInteger('frais_ecole_id')->nullable();
            $table->tinyInteger('statut_paiement')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('souscription_id')->nullable();
            $table->bigInteger('caisse_id')->nullable();
            $table->bigInteger('comptable_id')->nullable();
            $table->bigInteger('caissier_id')->nullable();
            $table->date('date_paiement')->nullable();
            $table->date('date_encaissement')->nullable();

            $table->integer('etat')->default(1);


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
        Schema::dropIfExists('details');
    }
};
