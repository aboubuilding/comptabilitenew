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
        Schema::create('chauffeurs', function (Blueprint $table) {
    $table->id();
    $table->string('nom');
    $table->string('prenom');
    $table->string('permis_conduire')->nullable()->unique();
    $table->date('date_validite_permis')->nullable();
    $table->string('telephone')->nullable();
    $table->string('email')->nullable();
    $table->text('adresse')->nullable();
    $table->tinyInteger('statut')->default(1); // 1=actif, 0=inactif
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
        Schema::dropIfExists('chauffeurs');
    }
};
