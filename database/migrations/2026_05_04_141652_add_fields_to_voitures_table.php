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
       Schema::table('voitures', function (Blueprint $table) {
    $table->string('modele')->nullable()->after('marque');
    $table->integer('annee_fabrication')->nullable()->after('nombre_place');
    $table->string('couleur')->nullable();
    $table->string('numero_chassis')->nullable()->unique();
    $table->date('date_achat')->nullable();
    $table->decimal('prix_achat', 12, 2)->nullable();
    $table->string('fournisseur')->nullable();
    $table->decimal('kilometrage_actuel', 12, 0)->default(0);
    $table->tinyInteger('statut')->default(1)->comment('1=disponible,2=en maintenance,3=sorti,4=réformé');
    $table->foreignId('annee_id')->nullable()->change(); // déjà existante, mais on peut ajouter contrainte
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voitures', function (Blueprint $table) {
            //
        });
    }
};
