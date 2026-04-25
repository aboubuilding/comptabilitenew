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
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();

            $table->date('date_inscription')->nullable();

            $table->integer('eleve_id')->nullable();
            $table->integer('cycle_id')->nullable();
            $table->integer('niveau_id')->nullable();
            $table->integer('last_niveau_id')->nullable();
            $table->integer('classe_id')->nullable();
            $table->integer('espace_id')->nullable();
            $table->tinyInteger('type_inscription')->nullable();
            $table->tinyInteger('statut_validation')->nullable();
            $table->integer('annee_id')->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('taux_remise')->nullable();

            //Validation
            $table->text('motif_rejet')->nullable();
            $table->dateTime('date_validation')->nullable();
            $table->integer('utilisateur_id')->nullable();

            //Scolarité

            $table->integer('specialite_id_1')->nullable();
            $table->integer('specialite_id_2')->nullable();
            $table->integer('specialite_id_3')->nullable();
            $table->string('specialite_abandonne')->nullable();
            $table->string('bulletin_1')->nullable();
            $table->string('bulletin_2')->nullable();
            $table->string('bulletin_3')->nullable();
            $table->string('dnb')->nullable();

            $table->tinyInteger('programme_provenance')->nullable();
            $table->tinyInteger('is_cantine')->nullable();
            $table->tinyInteger('is_bus')->nullable();
             $table->tinyInteger('is_livre')->nullable();
            $table->float('frais_scolarite')->nullable();
            $table->float('frais_assurance')->nullable();
            $table->float('frais_inscription')->nullable();
            $table->float('frais_cantine')->nullable();
            $table->float('frais_bus')->nullable();
            $table->float('frais_livre')->nullable();
            $table->float('remise_scolarite')->nullable();
            $table->float('frais_examen')->nullable();



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
        Schema::dropIfExists('inscriptions');
    }
};
