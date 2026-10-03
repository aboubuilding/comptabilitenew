<?php

namespace App\Domain\Logistique\Services;

use App\Domain\Logistique\DTO\VenteData;
use App\Domain\Logistique\Models\StockActuel;
use App\Domain\Logistique\Models\Vente;
use App\Domain\Logistique\Models\VenteDetail;
use App\Domain\Logistique\Repositories\VenteRepository;
use App\Domain\Logistique\Types\VenteStatutPaiement;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VenteService
{
    public function __construct(
        private VenteRepository $ventes,
        private StockService    $stocks,
    ) {}

    /**
     * Enregistre une vente complète :
     *  1) L'en-tête (référence, dates, totaux)
     *  2) Les lignes
     *  3) Décrémente le stock via StockService (module 3.3)
     */
    public function enregistrer(VenteData $data): Vente
    {
        if (empty($data->lignes)) {
            throw new RuntimeException("Une vente doit comporter au moins une ligne.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();
        if (! $anneeId) {
            throw new RuntimeException("Aucune année scolaire active.");
        }

        return DB::transaction(function () use ($data, $anneeId) {
            // 1) Vérifie que le stock est disponible AVANT toute écriture
            $this->verifierStockDisponible($data, $data->magasinId);

            // 2) En-tête
            $vente = $this->ventes->create([
                'reference'       => $this->ventes->genererReference(),
                'date_vente'      => $data->dateVente,
                'total_ht'        => $data->totalHt(),
                'total_ttc'       => $data->totalTtc(),
                'type_vente'      => $data->typeVente,
                'statut_paiement' => $data->marquerPayee
                    ? VenteStatutPaiement::PAYE->value
                    : VenteStatutPaiement::IMPAYE->value,
                'client_id'       => $data->clientId,
                'magasin_id'      => $data->magasinId,
                'inscription_id'  => $data->inscriptionId,
                'utilisateur_id'  => $data->utilisateurId,
                'annee_id'        => $anneeId,
            ]);

            // 3) Lignes + décrément de stock
            foreach ($data->lignes as $ligne) {
                VenteDetail::create([
                    'vente_id'       => $vente->id,
                    'produit_id'     => $ligne->produitId,
                    'quantite'       => $ligne->quantite,
                    'prix_unitaire'  => $ligne->prixUnitaire,
                    'remise'         => $ligne->remise,
                    'inscription_id' => $ligne->inscriptionId ?? $data->inscriptionId,
                    // montant_ht et montant_ttc sont générés par la base
                ]);

                // Décrément du stock
                $this->stocks->sortirDuStock(
                    produitId:     $ligne->produitId,
                    magasinId:     $data->magasinId,
                    quantite:      $ligne->quantite,
                    libelle:       "Vente {$vente->reference}",
                    utilisateurId: $data->utilisateurId,
                    motif:         "Point de vente — {$vente->reference}",
                );
            }

            return $vente->fresh('details');
        });
    }

    /** Annule une vente : réintègre le stock et passe le statut à ANNULE. */
    public function annuler(int $id, string $motif, int $userId): Vente
    {
        return DB::transaction(function () use ($id, $motif, $userId) {
            $vente = $this->ventes->findAvecDetails($id);

            if ($vente->isAnnulee()) {
                throw new RuntimeException("Cette vente est déjà annulée.");
            }

            // Réintègre le stock : on recrée une entrée par ligne
            foreach ($vente->details as $ligne) {
                if (! $ligne->produit_id) continue;

                $this->stocks->entrerEnStock(
                    produitId:     (int) $ligne->produit_id,
                    magasinId:     (int) $vente->magasin_id,
                    quantite:      (float) $ligne->quantite,
                    prixUnitaire:  (float) $ligne->prix_unitaire,
                    libelle:       "Annulation vente {$vente->reference}",
                    utilisateurId: $userId,
                    sourceType:    $vente::class,
                    sourceId:      $vente->id,
                );
            }

            $this->ventes->update($vente->id, [
                'statut_paiement' => VenteStatutPaiement::ANNULE->value,
            ]);

            Log::info('Vente annulée', [
                'vente_id' => $vente->id,
                'motif'    => $motif,
                'par'      => $userId,
            ]);

            return $vente->refresh();
        });
    }

    /** Marque une vente comme payée (si elle était impayée). */
    public function marquerPayee(int $id): Vente
    {
        $vente = $this->ventes->findOrFail($id);

        if ($vente->isAnnulee()) {
            throw new RuntimeException("Impossible de marquer payée une vente annulée.");
        }

        $this->ventes->update($vente->id, [
            'statut_paiement' => VenteStatutPaiement::PAYE->value,
        ]);

        return $vente->refresh();
    }

    /* ================================================================
       Internals
       ================================================================ */

    /**
     * Vérifie que le stock est suffisant pour chaque ligne AVANT de créer
     * la vente. Évite de créer une vente partielle si le stock manque.
     */
    private function verifierStockDisponible(VenteData $data, int $magasinId): void
    {
        foreach ($data->lignes as $ligne) {
            $stock = StockActuel::query()
                ->where('produit_id', $ligne->produitId)
                ->where('magasin_id', $magasinId)
                ->first();

            $disponible = (float) ($stock?->quantite ?? 0);

            if ($disponible < $ligne->quantite) {
                $produit = \App\Domain\Logistique\Models\Produit::find($ligne->produitId);
                throw new RuntimeException(
                    "Stock insuffisant pour « " . ($produit?->libelle ?? 'produit #' . $ligne->produitId) . " » : " .
                    "{$disponible} disponible(s), {$ligne->quantite} demandé(s)."
                );
            }
        }
    }
}