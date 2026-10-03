<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les bases ayant joué l'ancienne version de 2026_05_05_015440 ont
     * `prix_vente` à la place de `prix_unitaire`, que l'application utilise.
     */
    public function up(): void
    {
        if (Schema::hasColumn('produits', 'prix_vente') && !Schema::hasColumn('produits', 'prix_unitaire')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->renameColumn('prix_vente', 'prix_unitaire');
            });
        }
    }

    public function down(): void
    {
        // Rien à faire : prix_unitaire est le nom d'origine de la colonne
    }
};
