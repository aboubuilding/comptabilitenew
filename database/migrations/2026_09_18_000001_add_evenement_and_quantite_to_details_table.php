<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * evenement_id : signalé manquant par le cahier des charges §4 (2.2).
     * quantite : absent du schéma actuel, nécessaire pour afficher la
     * "Quantité (pour un produit)" demandée par le même écran — un achat
     * de produit n'enregistrait jusqu'ici que son montant total, sans
     * garder la quantité séparément.
     */
    public function up(): void
    {
        Schema::table('details', function (Blueprint $table) {
            if (! Schema::hasColumn('details', 'evenement_id')) {
                $table->foreignId('evenement_id')->nullable()->after('activite_id')
                    ->constrained('evenements')->nullOnDelete();
            }

            if (! Schema::hasColumn('details', 'quantite')) {
                $table->integer('quantite')->nullable()->after('produit_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('details', function (Blueprint $table) {
            if (Schema::hasColumn('details', 'evenement_id')) {
                $table->dropForeign(['evenement_id']);
                $table->dropColumn('evenement_id');
            }

            if (Schema::hasColumn('details', 'quantite')) {
                $table->dropColumn('quantite');
            }
        });
    }
};