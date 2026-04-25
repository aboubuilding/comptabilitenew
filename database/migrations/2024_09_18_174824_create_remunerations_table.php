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
        Schema::create('remunerations', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('employe_id')->nullable();
            $table->float('salaire_base')->nullable();
            $table->float('prime_anciennete')->nullable();
            $table->float('sursalaire')->nullable();
            $table->float('bonus')->nullable();
            $table->float('logement')->nullable();
            $table->float('nourriture')->nullable();
            $table->float('deplacement')->nullable();
            $table->float('autre_avantage')->nullable();

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
        Schema::dropIfExists('remunerations');
    }
};
