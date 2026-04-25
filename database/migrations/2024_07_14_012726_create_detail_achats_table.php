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
        Schema::create('detail_achats', function (Blueprint $table) {
            $table->id();



            $table->bigInteger('achat_id')->nullable();
            $table->bigInteger('produit_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->float('quantite')->nullable();
            $table->float('prix_unitaire')->nullable();
            $table->float('montant_achat')->nullable();


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
        Schema::dropIfExists('detail_achats');
    }
};
