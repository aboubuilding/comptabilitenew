<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Un frais de type produit, activité ou événement garde l'identifiant de la
 *   fiche qu'il alimente (synchronisation depuis l'écran des frais scolaires) :
 *   c'est ce lien qui évite les doublons d'une synchronisation à l'autre.
 * - Une tranche convertie en échéance garde la trace de l'échéance créée
 *   (conversion des tranches vers les plans d'échéancier, relançable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frais_ecoles', function (Blueprint $table) {
            foreach (['produit_id', 'activite_id', 'evenement_id'] as $colonne) {
                if (! Schema::hasColumn('frais_ecoles', $colonne)) {
                    $table->unsignedBigInteger($colonne)->nullable();
                }
            }
        });

        if (! Schema::hasColumn('tranches', 'plan_echeancier_ligne_id')) {
            Schema::table('tranches', fn (Blueprint $table) => $table->unsignedBigInteger('plan_echeancier_ligne_id')->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('tranches', fn (Blueprint $table) => $table->dropColumn('plan_echeancier_ligne_id'));
        Schema::table('frais_ecoles', fn (Blueprint $table) => $table->dropColumn(['produit_id', 'activite_id', 'evenement_id']));
    }
};
