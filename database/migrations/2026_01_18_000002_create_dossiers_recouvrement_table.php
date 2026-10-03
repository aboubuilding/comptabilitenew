<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers_recouvrement', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('inscription_id');
            $table->string('nature', 30);
            $table->float('montant_initial_du');
            $table->float('montant_recouvre')->default(0);

            // en_cours | echeancier | solde | irrecouvrable | contentieux
            $table->string('statut', 20)->default('en_cours');

            $table->date('date_ouverture');
            $table->date('date_cloture')->nullable();
            $table->string('motif_cloture')->nullable();

            $table->bigInteger('utilisateur_id')->nullable(); // responsable du dossier
            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index(['inscription_id', 'nature']);
            $table->index(['statut', 'annee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_recouvrement');
    }
};