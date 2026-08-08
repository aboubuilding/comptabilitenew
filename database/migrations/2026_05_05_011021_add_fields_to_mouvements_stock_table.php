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
       Schema::table('stocks', function (Blueprint $table) {
    $table->bigInteger('magasin_dest_id')->nullable();
    $table->string('reference')->nullable();
    $table->bigInteger('utilisateur_id')->nullable();
    // type_mouvement : 1 entrée, 2 sortie, 3 transfert
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mouvements_stock', function (Blueprint $table) {
            //
        });
    }
};
