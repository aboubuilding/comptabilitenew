<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Signalée manquante par le cahier des charges §4 (2.2). HYPOTHÈSE de
     * valeurs (à confirmer) : 1 = en attente, 2 = confirmée, 3 = annulée.
     */
    public function up(): void
    {
        Schema::table('participation_evenements', function (Blueprint $table) {
            if (! Schema::hasColumn('participation_evenements', 'statut')) {
                $table->tinyInteger('statut')->default(1)->after('montant_facture');
            }
        });
    }

    public function down(): void
    {
        Schema::table('participation_evenements', function (Blueprint $table) {
            if (Schema::hasColumn('participation_evenements', 'statut')) {
                $table->dropColumn('statut');
            }
        });
    }
};