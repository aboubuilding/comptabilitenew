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
        Schema::create('frais_ecoles', function (Blueprint $table) {
            $table->id();

            $table->string('libelle')->nullable();
            $table->float('montant')->nullable();
            $table->tinyInteger('type_paiement')->nullable();
            $table->tinyInteger('type_forfait')->nullable();
            $table->bigInteger('niveau_id')->nullable();
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
        Schema::dropIfExists('frais_ecoles');
    }
};
