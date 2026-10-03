<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regles_ventilation', function (Blueprint $table) {
            $table->id();

            // Déclencheur : nature de frais / type d'écriture.
            // ex. nature='cantine', nature='bus', nature='scolarite', ...
            $table->string('nature', 50);
            $table->string('sens', 10)->default('depense'); // recette | depense

            $table->unsignedBigInteger('centre_analytique_id');
            $table->float('pourcentage');    // part appliquée à ce centre
            $table->integer('priorite')->default(0); // ordre d'application

            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index(['nature', 'sens']);
            $table->foreign('centre_analytique_id')
                ->references('id')->on('centres_analytiques')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_ventilation');
    }
};