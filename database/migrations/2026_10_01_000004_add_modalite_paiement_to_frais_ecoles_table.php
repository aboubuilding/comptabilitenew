<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * frais_ecoles.type_paiement porte la nature du frais (TypeDetail, codes
     * historiques). La modalité comptant / échelonné (TypePaiement), ajoutée
     * depuis, a donc sa propre colonne.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('frais_ecoles', 'modalite_paiement')) {
            Schema::table('frais_ecoles', function (Blueprint $table) {
                $table->tinyInteger('modalite_paiement')->nullable()->after('type_paiement');
            });
        }

        // Un frais déjà rattaché à un plan d'échéancier est échelonné (TypePaiement::ECHELONNE = 2).
        // Les autres frais existants restent sans modalité : l'information n'existait pas.
        if (Schema::hasColumn('frais_ecoles', 'plan_echeancier_id')) {
            DB::table('frais_ecoles')
                ->whereNotNull('plan_echeancier_id')
                ->whereNull('modalite_paiement')
                ->update(['modalite_paiement' => 2]);
        }
    }

    public function down(): void
    {
        Schema::table('frais_ecoles', function (Blueprint $table) {
            $table->dropColumn('modalite_paiement');
        });
    }
};
