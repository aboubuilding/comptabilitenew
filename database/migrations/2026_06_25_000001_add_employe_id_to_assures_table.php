<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration additive : ajoute employe_id sur assures
 * pour s'aligner sur la table employes du domaine Rh.
 * On conserve personnel_id (nullable) pour compatibilité ascendante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assures', function (Blueprint $table) {
            if (!Schema::hasColumn('assures', 'employe_id')) {
                $table->bigInteger('employe_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('assures', 'statut')) {
                // Statut métier optionnel (actif/suspendu/termine) distinct de etat
                $table->string('statut')->default('actif')->after('prelevement_mensuel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assures', function (Blueprint $table) {
            if (Schema::hasColumn('assures', 'employe_id')) {
                $table->dropColumn('employe_id');
            }
            if (Schema::hasColumn('assures', 'statut')) {
                $table->dropColumn('statut');
            }
        });
    }
};