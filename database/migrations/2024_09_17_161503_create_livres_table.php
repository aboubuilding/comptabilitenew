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
        Schema::create('livres', function (Blueprint $table) {
            $table->id();


            $table->bigInteger('auteur_id')->nullable();
            $table->bigInteger('maison_edition_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('categorie_livre_id')->nullable();
            $table->bigInteger('annee_edition_id')->nullable();
            $table->string('titre')->nullable();
            $table->string('numero')->nullable();
            $table->string('numero_isbn')->nullable();


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
        Schema::dropIfExists('livres');
    }
};
