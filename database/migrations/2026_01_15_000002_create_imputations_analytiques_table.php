<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imputations_analytiques', function (Blueprint $table) {
            $table->id();

            // Lien polymorphique vers la source (Depense, Mouvement, Detail, Vente...)
            $table->string('imputable_type', 120);
            $table->unsignedBigInteger('imputable_id');

            $table->unsignedBigInteger('centre_analytique_id');
            $table->bigInteger('annee_id')->nullable();

            // Ventilation : soit un montant fixe, soit un pourcentage.
            // L'un des deux est NULL — jamais les deux remplis ni les deux vides.
            $table->float('montant')->nullable();
            $table->float('pourcentage')->nullable();

            // Sens de l'écriture (permet de distinguer recette / dépense
            // même quand la source ne le porte pas explicitement).
            $table->string('sens', 10)->default('depense'); // recette | depense

            $table->bigInteger('cree_par')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index(['imputable_type', 'imputable_id'], 'imp_source_idx');
            $table->index('centre_analytique_id');
            $table->index('annee_id');

            $table->foreign('centre_analytique_id')
                ->references('id')->on('centres_analytiques')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imputations_analytiques');
    }
};