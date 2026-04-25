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
        Schema::create('depense_voitures', function (Blueprint $table) {
            $table->id();

            $table->string('libelle')->nullable();
            $table->date('date_depense')->nullable();
            $table->float('montant')->nullable();
            $table->bigInteger('voiture_id')->nullable();
            $table->bigInteger('zone_id')->nullable();
            $table->tinyInteger('type_depense')->nullable();
            $table->bigInteger('annee_id')->nullable();


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
        Schema::dropIfExists('depense_voitures');
    }
};
