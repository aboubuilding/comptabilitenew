<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index utiles pour filtrer/affecter rapidement les inscriptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->index(['annee_id', 'statut_validation'], 'inscriptions_annee_statut_idx');
            $table->index(['eleve_id', 'annee_id', 'statut_validation'], 'inscriptions_eleve_annee_statut_idx');
            $table->index(['niveau_id', 'annee_id'], 'inscriptions_niveau_annee_idx');
            $table->index(['classe_id', 'annee_id'], 'inscriptions_classe_annee_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropIndex('inscriptions_annee_statut_idx');
            $table->dropIndex('inscriptions_eleve_annee_statut_idx');
            $table->dropIndex('inscriptions_niveau_annee_idx');
            $table->dropIndex('inscriptions_classe_annee_idx');
        });
    }
};