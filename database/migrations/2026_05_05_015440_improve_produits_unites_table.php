<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('produits', function (Blueprint $table) {
            // Gestion avancée des unités
            if (!Schema::hasColumn('produits', 'code')) {
                $table->string('code')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('produits', 'categorie')) {
                $table->string('categorie')->nullable()->after('libelle');
            }
            
            // Unité de base (la plus petite, ex: pièce, gramme, ml)
            if (!Schema::hasColumn('produits', 'unite_base')) {
                $table->string('unite_base')->default('piece')->after('prix_unitaire');
            }
            
            // Unité d'achat (ex: carton, boîte, kg)
            if (!Schema::hasColumn('produits', 'unite_achat')) {
                $table->string('unite_achat')->nullable()->after('unite_base');
            }
            if (!Schema::hasColumn('produits', 'conversion_achat')) {
                $table->float('conversion_achat')->default(1)->after('unite_achat'); // 1 carton = X unités_base
            }
            
            // Unité de vente (ex: pièce, paquet, sachet)
            if (!Schema::hasColumn('produits', 'unite_vente')) {
                $table->string('unite_vente')->nullable()->after('conversion_achat');
            }
            if (!Schema::hasColumn('produits', 'conversion_vente')) {
                $table->float('conversion_vente')->default(1)->after('unite_vente'); // 1 pièce = X unités_base
            }
            
            // Gestion des prix multiples
            if (!Schema::hasColumn('produits', 'prix_achat')) {
                $table->decimal('prix_achat', 12, 2)->nullable()->after('prix_unitaire');
            }
            if (!Schema::hasColumn('produits', 'prix_vente')) {
                $table->renameColumn('prix_unitaire', 'prix_vente');
            }
            
            // Stock minimum et alerte (déjà existants ou à ajouter)
            if (!Schema::hasColumn('produits', 'quantite_stock')) {
                $table->float('quantite_stock')->default(0)->after('prix_vente');
            }
            if (!Schema::hasColumn('produits', 'seuil_alerte')) {
                $table->float('seuil_alerte')->default(5)->after('quantite_stock');
            }
            if (!Schema::hasColumn('produits', 'stock_min')) {
                $table->float('stock_min')->default(0)->after('seuil_alerte');
            }
            if (!Schema::hasColumn('produits', 'stock_max')) {
                $table->float('stock_max')->nullable()->after('stock_min');
            }
            
            // Autres
            if (!Schema::hasColumn('produits', 'description')) {
                $table->text('description')->nullable()->after('libelle');
            }
        });
    }

    public function down()
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn([
                'code', 'categorie', 'description', 'unite_base', 'conversion_achat',
                'unite_vente', 'conversion_vente', 'prix_achat', 'quantite_stock',
                'seuil_alerte', 'stock_min', 'stock_max'
            ]);
            // Renommer prix_vente vers prix_unitaire si besoin
            if (Schema::hasColumn('produits', 'prix_vente')) {
                $table->renameColumn('prix_vente', 'prix_unitaire');
            }
        });
    }
};