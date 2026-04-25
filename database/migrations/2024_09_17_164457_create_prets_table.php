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
        Schema::create('prets', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('livre_id')->nullable();
            $table->bigInteger('inscription_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->date('date_pret')->nullable();
            $table->date('date_retour_pret')->nullable();
            $table->date('date_retour_reel')->nullable();
            $table->integer('statut_pret')->nullable();
         
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
        Schema::dropIfExists('prets');
    }
};
