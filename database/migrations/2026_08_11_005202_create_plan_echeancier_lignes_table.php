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
        Schema::create('plan_echeancier_lignes', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('plan_echeancier_id')->nullable();
            $table->integer('ordre')->nullable();
            $table->integer('jour_echeance')->nullable();
            $table->date('date_echeance')->nullable();
            $table->decimal('montant',15,2)->nullable();
            $table->decimal('pourcentage',5,2) ->nullable();
            $table->string('libelle');
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
        Schema::dropIfExists('plan_echeancier_lignes');
    }
};
