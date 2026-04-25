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
        Schema::create('mouvements', function (Blueprint $table) {
            $table->id();

            $table->string('libelle');
            $table->string('beneficiaire')->nullable();
            $table->mediumText('motif')->nullable();
            $table->date('date_mouvement');
            $table->float('montant');
            $table->tinyInteger('type_mouvement')->nullable();
            $table->bigInteger('caisse_id');
            $table->bigInteger('utilisateur_id')->nullable();
            $table->bigInteger('paiement_id')->nullable();
            $table->bigInteger('depense_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->string('file')->nullable();
            $table->tinyInteger('statut_mouvement')->nullable();
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
        Schema::dropIfExists('mouvements');
    }
};
