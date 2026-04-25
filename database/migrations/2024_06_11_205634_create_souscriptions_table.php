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
        Schema::create('souscriptions', function (Blueprint $table) {
            $table->id();

            $table->date('date_souscription')->nullable();
            $table->float('montant_annuel_prevu')->nullable();
            $table->float('taux_remise')->nullable();
            $table->tinyInteger('type_paiement')->nullable();
            $table->bigInteger('frais_ecole_id')->nullable();
            $table->bigInteger('niveau_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('inscription_id')->nullable();


            $table->bigInteger('utilisateur_id')->nullable();

            $table->bigInteger('ligne_id')->nullable();
            $table->bigInteger('zone_id')->nullable();

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
        Schema::dropIfExists('souscriptions');
    }
};
