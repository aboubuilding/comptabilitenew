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
        Schema::create('stock_actuel', function (Blueprint $table) {
    $table->id();
    $table->foreignId('produit_id')->constrained();
    $table->foreignId('magasin_id')->constrained();
    $table->float('quantite')->default(0);
    $table->float('seuil_alerte')->nullable();
    $table->timestamps();

    $table->unique(['produit_id', 'magasin_id']);
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_actuel');
    }
};
