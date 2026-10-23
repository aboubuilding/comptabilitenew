<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Montant réellement décaissé d'une dépense, confirmé par le caissier au
 * décaissement (il peut différer du montant de la dépense). Le mouvement de
 * sortie inscrit au journal de caisse porte ce montant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            if (! Schema::hasColumn('depenses', 'montant_decaisse')) {
                $table->decimal('montant_decaisse', 12, 2)->nullable()->after('regle_par');
            }
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropColumn('montant_decaisse');
        });
    }
};
