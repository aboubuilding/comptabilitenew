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
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();

            $table->date('date_vente')->nullable();
            $table->float('quantite')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('inscription_id')->nullable();
            $table->bigInteger('paiement_id')->nullable();
            $table->bigInteger('produit_id')->nullable();
            $table->bigInteger('detail_id')->nullable();
            $table->bigInteger('utilisateur_id')->nullable();

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
        Schema::dropIfExists('ventes');
    }
};
