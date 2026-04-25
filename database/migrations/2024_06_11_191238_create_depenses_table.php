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
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();

             $table->string('libelle')->nullable();
            $table->string('beneficaire')->nullable();
            $table->text('motif_depense')->nullable();
            $table->date('date_depense')->nullable();

            $table->integer('montant')->nullable();
            $table->bigInteger('annee_id')->nullable();

            $table->bigInteger('utilisateur_id')->nullable();

            $table->tinyInteger('statut_depense')->nullable();
         

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
        Schema::dropIfExists('depenses');
    }
};
