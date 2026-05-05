<?php
// database/migrations/2025_05_04_000000_update_produits_and_details_for_stock.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ajout des champs de stock à la table 'produits'
        if (Schema::hasTable('produits')) {
            Schema::table('produits', function (Blueprint $table) {
                if (!Schema::hasColumn('produits', 'quantite_stock')) {
                    $table->integer('quantite_stock')->default(0)->after('prix_unitaire');
                }
                if (!Schema::hasColumn('produits', 'seuil_alerte')) {
                    $table->integer('seuil_alerte')->default(5)->after('quantite_stock');
                }
                if (!Schema::hasColumn('produits', 'stock_min')) {
                    $table->integer('stock_min')->default(0)->after('seuil_alerte');
                }
                if (!Schema::hasColumn('produits', 'stock_max')) {
                    $table->integer('stock_max')->nullable()->after('stock_min');
                }
            });
        }

        // 2. Ajout de la colonne 'produit_id' dans 'details'
        if (Schema::hasTable('details') && !Schema::hasColumn('details', 'produit_id')) {
            Schema::table('details', function (Blueprint $table) {
                $table->foreignId('produit_id')->nullable()->after('frais_ecole_id')
                      ->constrained('produits')->nullOnDelete();
            });
        }

        // 3. Création de la table 'stock_mouvements' si elle n'existe pas
        if (!Schema::hasTable('stock_mouvements')) {
            Schema::create('stock_mouvements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
                $table->enum('type', ['entree', 'sortie', 'ajustement']);
                $table->integer('quantite');
                $table->string('motif')->nullable();
                $table->bigInteger('reference_id')->nullable();
                $table->foreignId('utilisateur_id')->nullable()->constrained('users');
                $table->date('date_mouvement');
                $table->timestamps();

                $table->index('produit_id');
                $table->index('reference_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Suppression de la table stock_mouvements
        Schema::dropIfExists('stock_mouvements');

        // Suppression de la colonne produit_id dans details
        if (Schema::hasTable('details') && Schema::hasColumn('details', 'produit_id')) {
            Schema::table('details', function (Blueprint $table) {
                $table->dropForeign(['produit_id']);
                $table->dropColumn('produit_id');
            });
        }

        // Suppression des champs de stock dans produits
        if (Schema::hasTable('produits')) {
            Schema::table('produits', function (Blueprint $table) {
                $columns = ['quantite_stock', 'seuil_alerte', 'stock_min', 'stock_max'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('produits', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};