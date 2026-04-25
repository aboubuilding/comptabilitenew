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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();

            $table->string('numero');
            $table->string('emetteur');
            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('paiement_id')->nullable();
            $table->date('date_emission')->nullable();
            $table->tinyInteger('statut')->nullable();
            $table->date('date_encaissement')->nullable();
            $table->bigInteger('banque_id')->nullable();
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
        Schema::dropIfExists('cheques');
    }
};
