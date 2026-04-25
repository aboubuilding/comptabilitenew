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
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();

            $table->string('matricule')->nullable();
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('prenom_usuel')->nullable();
            $table->string('ecole_provenance')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->tinyInteger('sexe')->nullable();
            $table->integer('nationalite_id')->nullable();
            $table->integer('espace_id')->nullable();
            $table->string('nom_medecin')->nullable();

            $table->text('personne_prevenir')->nullable();
            $table->string('photo')->nullable();
            $table->string('carte_identite')->nullable();
            $table->string('naissance')->nullable();

            //infos de santé
            $table->tinyInteger('groupe_id')->nullable();
            $table->string('certificat_medical')->nullable();
            $table->string('vaccin_1')->nullable();
            $table->string('vaccin_2')->nullable();
            $table->string('vaccin_3')->nullable();
            $table->string('vaccin_4')->nullable();
            $table->string('vaccin_5')->nullable();

            $table->string('numero_medecin')->nullable();
            $table->string('numero_personne_prevenir')->nullable();
            $table->tinyInteger('lien_parente_personne')->nullable();
            $table->string('naissance_eleve')->nullable();

            $table->text('allergie')->nullable();

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
        Schema::dropIfExists('eleves');
    }
};
