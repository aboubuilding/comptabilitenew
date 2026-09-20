<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table entièrement manquante, signalée par le cahier des charges
     * §4 (2.2) — nécessaire pour qu'un élève puisse être "engagé" sur
     * une activité (au même titre qu'une inscription cantine/bus),
     * détectable par l'écran Paiements.
     *
     * date_inscription ajouté en plus des champs validés (par symétrie
     * avec participation_evenements, qui en a un) — à retirer si non
     * souhaité.
     */
    public function up(): void
    {
        Schema::create('inscriptions_activites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('activite_id')->constrained('activites')->cascadeOnDelete();
            $table->bigInteger('annee_id')->nullable();

            $table->date('date_inscription')->nullable();
            $table->decimal('montant_du', 15, 2)->default(0);

            // HYPOTHÈSE de valeurs (à confirmer) : 1 = en attente, 2 = confirmée, 3 = annulée
            $table->tinyInteger('statut')->default(1);

            $table->integer('etat')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions_activites');
    }
};