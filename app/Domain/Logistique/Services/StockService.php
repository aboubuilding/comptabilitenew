<?php

namespace App\Domain\Logistique\Services;

use App\Domain\Logistique\Models\Magasin;
use App\Domain\Logistique\Models\Produit;
use App\Domain\Logistique\Models\Stock;
use App\Domain\Logistique\Models\StockActuel;
use App\Domain\Logistique\Types\TypeMouvementStock;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * Point d'entrée UNIQUE pour toute modification de stock.
     * Écrit 1 ligne dans `stocks` (journal) et met à jour `stock_actuel`
     * (solde) dans la même transaction.
     *
     * @param  int    $produitId
     * @param  int    $magasinId
     * @param  float  $quantite        toujours positif
     * @param  TypeMouvementStock $type
     * @param  array  $meta            ['prix_unitaire', 'libelle', 'motif',
     *                                  'utilisateur_id', 'bon_id',
     *                                  'source_type', 'source_id', 'date_stock']
     */
    public function enregistrer(
        int $produitId,
        int $magasinId,
        float $quantite,
        TypeMouvementStock $type,
        array $meta = []
    ): Stock {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité doit être strictement positive.");
        }

        return DB::transaction(function () use ($produitId, $magasinId, $quantite, $type, $meta) {
            $anneeId = app(AnneeScolaireContext::class)->id();

            // 1) Récupère / crée le solde actuel
            $stockActuel = StockActuel::firstOrCreate(
                ['produit_id' => $produitId, 'magasin_id' => $magasinId],
                ['quantite' => 0, 'valeur_stock' => 0]
            );

            $quantiteAvant = (float) $stockActuel->quantite;
            // Un ajustement d'inventaire peut aller dans les deux sens : ajuster() le précise
            $signe = $meta['signe'] ?? $type->signe();
            $delta = $signe * $quantite;
            $quantiteApres = round($quantiteAvant + $delta, 4);

            // Interdit de rendre le stock négatif (sauf ajustement explicite)
            if ($quantiteApres < 0 && $type !== TypeMouvementStock::AJUSTEMENT) {
                throw new RuntimeException(
                    "Stock insuffisant : {$quantiteAvant} disponible, {$quantite} demandé."
                );
            }

            // 2) Valorisation : prix unitaire fourni ou dernier prix connu
            $prixUnitaire = isset($meta['prix_unitaire'])
                ? (float) $meta['prix_unitaire']
                : $this->dernierPrixConnu($produitId, $magasinId);

            $valeurMouvement = round($quantite * $prixUnitaire, 2);

            // 3) Calcul du nouveau solde et de sa valeur (prix moyen pondéré)
            $nouvelleValeurStock = $this->calculerNouvelleValeur(
                ancienneValeur: (float) $stockActuel->valeur_stock,
                ancienneQuantite: $quantiteAvant,
                delta: $delta,
                valeurMouvement: $valeurMouvement,
            );

            // 4) Journal : une ligne par mouvement
            $mouvement = Stock::create([
                'date_stock'       => $meta['date_stock'] ?? now()->toDateString(),
                'produit_id'       => $produitId,
                'magasin_id'       => $magasinId,
                'bon_id'           => $meta['bon_id'] ?? null,
                'quantite'         => $quantite,
                'type_mouvement'   => $type->value,
                'prix_unitaire'    => $prixUnitaire,
                'valeur_mouvement' => $valeurMouvement,
                'libelle'          => $meta['libelle'] ?? null,
                'motif'            => $meta['motif'] ?? null,
                'utilisateur_id'   => $meta['utilisateur_id'] ?? null,
                'source_type'      => $meta['source_type'] ?? null,
                'source_id'        => $meta['source_id'] ?? null,
                'annee_id'         => $anneeId,
            ]);

            // 5) Solde : mise à jour
            $stockActuel->update([
                'quantite'               => $quantiteApres,
                'valeur_stock'           => $nouvelleValeurStock,
                'date_dernier_mouvement' => now()->toDateString(),
            ]);

            return $mouvement;
        });
    }

    /** Raccourci : entrée en stock. */
    public function entrerEnStock(
        int $produitId,
        int $magasinId,
        float $quantite,
        float $prixUnitaire,
        string $libelle,
        ?int $utilisateurId = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $bonId = null,
    ): Stock {
        return $this->enregistrer($produitId, $magasinId, $quantite, TypeMouvementStock::ENTREE, [
            'prix_unitaire'  => $prixUnitaire,
            'libelle'        => $libelle,
            'utilisateur_id' => $utilisateurId,
            'source_type'    => $sourceType,
            'source_id'      => $sourceId,
            'bon_id'         => $bonId,
        ]);
    }

    /** Raccourci : sortie de stock. */
    public function sortirDuStock(
        int $produitId,
        int $magasinId,
        float $quantite,
        string $libelle,
        ?int $utilisateurId = null,
        ?string $motif = null,
        ?int $bonId = null,
    ): Stock {
        return $this->enregistrer($produitId, $magasinId, $quantite, TypeMouvementStock::SORTIE, [
            'libelle'        => $libelle,
            'motif'          => $motif,
            'utilisateur_id' => $utilisateurId,
            'bon_id'         => $bonId,
        ]);
    }

    /** Raccourci : ajustement (correction d'inventaire). */
    public function ajuster(
        int $produitId,
        int $magasinId,
        float $nouvelleQuantite,
        string $motif,
        int $utilisateurId,
    ): Stock {
        $stockActuel = StockActuel::firstOrCreate(
            ['produit_id' => $produitId, 'magasin_id' => $magasinId],
            ['quantite' => 0, 'valeur_stock' => 0]
        );

        $ecart = $nouvelleQuantite - (float) $stockActuel->quantite;
        if (abs($ecart) < 0.0001) {
            throw new RuntimeException("Aucun écart à ajuster.");
        }

        return $this->enregistrer(
            $produitId, $magasinId,
            abs($ecart),
            TypeMouvementStock::AJUSTEMENT,
            [
                'libelle'        => "Ajustement d'inventaire",
                'motif'          => $motif . " (nouvelle qté : {$nouvelleQuantite})",
                'utilisateur_id' => $utilisateurId,
                'signe'          => $ecart > 0 ? 1 : -1,
            ]
        );
    }

    /**
     * Transfert entre deux magasins : 2 mouvements dans la même transaction.
     */
    public function transferer(
        int $produitId,
        int $magasinSourceId,
        int $magasinDestId,
        float $quantite,
        ?int $bonId = null,
        ?int $utilisateurId = null,
        ?string $motif = null,
    ): array {
        if ($magasinSourceId === $magasinDestId) {
            throw new RuntimeException("Le magasin source et le magasin destination doivent être différents.");
        }

        return DB::transaction(function () use ($produitId, $magasinSourceId, $magasinDestId, $quantite, $bonId, $utilisateurId, $motif) {
            $libelle = $motif ?? 'Transfert entre magasins';

            $sortie = $this->enregistrer($produitId, $magasinSourceId, $quantite, TypeMouvementStock::TRANSFERT_SORTIE, [
                'libelle'        => $libelle . ' (sortie)',
                'utilisateur_id' => $utilisateurId,
                'bon_id'         => $bonId,
            ]);

            $entree = $this->enregistrer($produitId, $magasinDestId, $quantite, TypeMouvementStock::TRANSFERT_ENTREE, [
                'prix_unitaire'  => $sortie->prix_unitaire,
                'libelle'        => $libelle . ' (entrée)',
                'utilisateur_id' => $utilisateurId,
                'bon_id'         => $bonId,
            ]);

            return ['sortie' => $sortie, 'entree' => $entree];
        });
    }

    /* ================================================================
       Internals
       ================================================================ */

    private function dernierPrixConnu(int $produitId, int $magasinId): float
    {
        $dernier = Stock::query()
            ->where('produit_id', $produitId)
            ->where('magasin_id', $magasinId)
            ->whereNotNull('prix_unitaire')
            ->orderByDesc('date_stock')
            ->orderByDesc('id')
            ->value('prix_unitaire');

        if ($dernier !== null) return (float) $dernier;

        $produit = Produit::find($produitId);
        return (float) ($produit?->prix_unitaire ?? 0);
    }

    /** Calcule la nouvelle valeur de stock selon le prix moyen pondéré. */
    private function calculerNouvelleValeur(
        float $ancienneValeur,
        float $ancienneQuantite,
        float $delta,
        float $valeurMouvement
    ): float {
        // Entrée : on ajoute la valeur du mouvement
        if ($delta > 0) {
            return round($ancienneValeur + $valeurMouvement, 2);
        }

        // Sortie : on retire au prix moyen actuel
        if ($ancienneQuantite > 0) {
            $prixMoyen = $ancienneValeur / $ancienneQuantite;
            return round(max(0, $ancienneValeur + $delta * $prixMoyen), 2);
        }

        return 0.0;
    }
}