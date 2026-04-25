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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();

            $table->date('date_souscription')->nullable();
            $table->float('montant_annuel_prevu')->nullable();
            $table->string('destination')->nullable();
            $table->text('adresse_map')->nullable();

            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('inscription_id')->nullable();

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
        Schema::dropIfExists('cars');
    }
};
