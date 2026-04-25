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
        Schema::create('produits', function (Blueprint $table) {
            $table->id();

            $table->string('libelle')->nullable();
            $table->float('prix_unitaire')->nullable();
            $table->string('photo')->nullable();
            $table->string('unite_stock')->nullable();
            $table->string('unite_achat')->nullable();
            $table->float('equivalence')->nullable();
            $table->tinyInteger('type_produit')->nullable();

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
        Schema::dropIfExists('produits');
    }
};
