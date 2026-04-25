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
        Schema::create('annees', function (Blueprint $table) {

            $table->id();

            $table->string('libelle')->nullable();
            $table->date('date_rentree')->nullable();
            $table->date('date_fin')->nullable();
            $table->date('date_ouverture_inscription')->nullable();
            $table->date('date_fermeture_reinscription')->nullable();
            $table->tinyInteger('statut_annee')->nullable();

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
        Schema::dropIfExists('annees');
    }
};
