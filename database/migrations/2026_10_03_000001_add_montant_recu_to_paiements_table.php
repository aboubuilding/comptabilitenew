<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Montant réellement remis au caissier lors de l'encaissement d'un paiement.
 * La différence avec le montant encaissé est la monnaie rendue (espèces).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('paiements', 'montant_recu')) {
            Schema::table('paiements', function (Blueprint $table) {
                $table->decimal('montant_recu', 15, 2)->nullable()->after('montant');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('paiements', 'montant_recu')) {
            Schema::table('paiements', function (Blueprint $table) {
                $table->dropColumn('montant_recu');
            });
        }
    }
};
