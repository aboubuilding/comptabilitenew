<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index utiles pour la recherche d'élèves (matricule, nom).
 * Aucune modification destructive : on garde la structure existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            $table->index('matricule');
            $table->index(['nom', 'prenom']);
        });
    }

    public function down(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            $table->dropIndex(['matricule']);
            $table->dropIndex(['nom', 'prenom']);
        });
    }
};