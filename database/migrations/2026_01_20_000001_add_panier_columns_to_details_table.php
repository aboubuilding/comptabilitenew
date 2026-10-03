<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('details', function (Blueprint $table) {
            // Panier libre : lien vers les 4 sources possibles
            $table->unsignedBigInteger('produit_id')->nullable()->after('souscription_id');
            $table->unsignedBigInteger('service_id')->nullable()->after('produit_id');
            $table->unsignedBigInteger('activite_id')->nullable()->after('service_id');
            $table->unsignedBigInteger('evenement_id')->nullable()->after('activite_id');

            // Détails pour les produits (quantité × prix unitaire)
            $table->float('quantite')->nullable()->after('montant');
            $table->float('prix_unitaire')->nullable()->after('quantite');
        });
    }

    public function down(): void
    {
        Schema::table('details', function (Blueprint $table) {
            $table->dropColumn([
                'produit_id', 'service_id', 'activite_id', 'evenement_id',
                'quantite', 'prix_unitaire',
            ]);
        });
    }
};