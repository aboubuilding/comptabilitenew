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
        Schema::create('participation_evenements', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('evenement_scolaire_id')->nullable();
            $table->bigInteger('inscripion_id')->nullable();
            $table->date('date_inscription');
            $table->decimal('montant_facture',15,2)->default(0);
            $table->bigInteger('facture_id')->default(false);
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
        Schema::dropIfExists('participation_evenements');
    }
};
