<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les migrations de création (2024_*) ont été réécrites après la mise en
     * production : une base historique les a déjà jouées dans leur ancienne
     * version et n'a donc pas les colonnes ajoutées depuis. On complète ici,
     * sans rien supprimer — chaque ajout est ignoré s'il est déjà en place.
     */
    public function up(): void
    {
        $this->ajouter('achats', [
            'montant_total' => fn (Blueprint $t) => $t->decimal('montant_total', 12, 2)->nullable(),
            'type_achat'    => fn (Blueprint $t) => $t->tinyInteger('type_achat')->default(1),
        ]);

        $this->ajouter('detail_achats', [
            'montant_achat' => fn (Blueprint $t) => $t->float('montant_achat')->nullable(),
        ]);

        $this->ajouter('chauffeurs', [
            'permis_conduire'      => fn (Blueprint $t) => $t->string('permis_conduire')->nullable()->unique(),
            'date_validite_permis' => fn (Blueprint $t) => $t->date('date_validite_permis')->nullable(),
            'email'                => fn (Blueprint $t) => $t->string('email')->nullable(),
            'adresse'              => fn (Blueprint $t) => $t->text('adresse')->nullable(),
            'statut'               => fn (Blueprint $t) => $t->tinyInteger('statut')->default(1),
        ]);

        $this->ajouter('magasins', [
            'adresse'   => fn (Blueprint $t) => $t->text('adresse')->nullable(),
            'telephone' => fn (Blueprint $t) => $t->string('telephone')->nullable(),
            'type'      => fn (Blueprint $t) => $t->tinyInteger('type')->default(1),
        ]);

        $this->ajouter('produits', [
            'unite_stock'  => fn (Blueprint $t) => $t->string('unite_stock')->nullable(),
            'equivalence'  => fn (Blueprint $t) => $t->float('equivalence')->nullable(),
            'type_produit' => fn (Blueprint $t) => $t->tinyInteger('type_produit')->nullable(),
        ]);

        $this->ajouter('souscriptions', [
            'zone_id' => fn (Blueprint $t) => $t->bigInteger('zone_id')->nullable(),
        ]);

        // Colonnes écrites par CaisseService / CantineService mais absentes de toute migration
        $this->ajouter('caisses', [
            'solde_compte' => fn (Blueprint $t) => $t->float('solde_compte')->nullable(),
            'ecart'        => fn (Blueprint $t) => $t->float('ecart')->nullable(),
            'valide_par'   => fn (Blueprint $t) => $t->bigInteger('valide_par')->nullable(),
        ]);

        $this->ajouter('inscriptions_cantine', [
            'annee_id' => fn (Blueprint $t) => $t->bigInteger('annee_id')->nullable(),
        ]);

        $this->alignerDepenses();
        $this->alignerTranches();
        $this->alignerVentes();
    }

    public function down(): void
    {
        // Volontairement vide : on ne retire pas des colonnes susceptibles de porter des données
    }

    /**
     * @param array<string, callable(Blueprint): mixed> $colonnes
     */
    private function ajouter(string $table, array $colonnes): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $manquantes = array_filter(
            $colonnes,
            fn (string $colonne) => !Schema::hasColumn($table, $colonne),
            ARRAY_FILTER_USE_KEY
        );

        if ($manquantes === []) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($manquantes) {
            foreach ($manquantes as $definir) {
                $definir($t);
            }
        });
    }

    /** Ancienne faute de frappe : `beneficaire` → `beneficiaire`. */
    private function alignerDepenses(): void
    {
        if (Schema::hasColumn('depenses', 'beneficaire') && !Schema::hasColumn('depenses', 'beneficiaire')) {
            Schema::table('depenses', function (Blueprint $t) {
                $t->renameColumn('beneficaire', 'beneficiaire');
            });
        }
    }

    /**
     * L'ancien format (tranche_1..3 / date_1..3 par niveau) est conservé tel quel ;
     * on ajoute le format actuel (une ligne par échéance de frais).
     */
    private function alignerTranches(): void
    {
        $this->ajouter('tranches', [
            'libelle'        => fn (Blueprint $t) => $t->string('libelle')->nullable(),
            'date_butoire'   => fn (Blueprint $t) => $t->date('date_butoire')->nullable(),
            'frais_ecole_id' => fn (Blueprint $t) => $t->integer('frais_ecole_id')->nullable(),
            'type_frais'     => fn (Blueprint $t) => $t->integer('type_frais')->nullable(),
            'taux'           => fn (Blueprint $t) => $t->integer('taux')->nullable(),
        ]);

        // Anciennes colonnes obligatoires que le modèle actuel ne renseigne plus
        foreach (['annee_id', 'niveau_id'] as $colonne) {
            if (Schema::hasColumn('tranches', $colonne)) {
                Schema::table('tranches', function (Blueprint $t) use ($colonne) {
                    $t->bigInteger($colonne)->nullable()->change();
                });
            }
        }
    }

    private function alignerVentes(): void
    {
        $this->ajouter('ventes', [
            'reference'        => fn (Blueprint $t) => $t->string('reference', 50)->nullable(),
            'total_ht'         => fn (Blueprint $t) => $t->decimal('total_ht', 12, 2)->nullable(),
            'total_ttc'        => fn (Blueprint $t) => $t->decimal('total_ttc', 12, 2)->nullable(),
            'type_vente'       => fn (Blueprint $t) => $t->tinyInteger('type_vente')->default(1),
            'statut_paiement'  => fn (Blueprint $t) => $t->tinyInteger('statut_paiement')->default(0),
            'magasin_id'       => fn (Blueprint $t) => $t->unsignedBigInteger('magasin_id')->nullable(),
            'client_id'        => fn (Blueprint $t) => $t->unsignedBigInteger('client_id')->nullable(),
            'date_reversement' => fn (Blueprint $t) => $t->date('date_reversement')->nullable(),
            'reversement_id'   => fn (Blueprint $t) => $t->bigInteger('reversement_id')->nullable(),
            'caissier_id'      => fn (Blueprint $t) => $t->bigInteger('caissier_id')->nullable(),
        ]);

        $index = [
            'ventes_annee_id_date_vente_index' => ['annee_id', 'date_vente'],
            'ventes_statut_paiement_index'     => ['statut_paiement'],
            'ventes_reference_index'           => ['reference'],
            'ventes_reversement_id_index'      => ['reversement_id'],
        ];

        $cles = [
            'ventes_magasin_id_foreign' => ['magasin_id', 'magasins'],
            'ventes_client_id_foreign'  => ['client_id', 'clients'],
        ];

        Schema::table('ventes', function (Blueprint $t) use ($index, $cles) {
            foreach ($index as $nom => $colonnes) {
                if (!$this->indexExiste('ventes', $nom)) {
                    $t->index($colonnes, $nom);
                }
            }

            foreach ($cles as $nom => [$colonne, $cible]) {
                if (!$this->cleEtrangereExiste('ventes', $nom)) {
                    $t->foreign($colonne, $nom)->references('id')->on($cible)->nullOnDelete();
                }
            }
        });
    }

    private function indexExiste(string $table, string $nom): bool
    {
        return DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$nom]) !== [];
    }

    private function cleEtrangereExiste(string $table, string $nom): bool
    {
        return DB::select(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ?
                AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $nom]
        ) !== [];
    }
};
