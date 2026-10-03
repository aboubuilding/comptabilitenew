<?php

namespace App\Domain\Logistique\Services;

use App\Domain\Logistique\Models\Bon;
use App\Domain\Logistique\Models\DetailBon;
use App\Domain\Logistique\Repositories\BonRepository;
use App\Domain\Logistique\Types\TypeBon;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BonService
{
    public function __construct(
        private BonRepository $bons,
        private StockService  $stocks,
    ) {}

    /**
     * Crée un bon et applique immédiatement les mouvements de stock.
     * Pour un transfert, crée les 2 mouvements (sortie + entrée).
     *
     * @param  array $data ['type','date_bon','magasin_id','magasin_destination_id',
     *                      'nom_responsable','nom_magasinier','motif','commentaire',
     *                      'lignes' => [['produit_id','quantite','prix_unitaire']]]
     */
    public function creer(array $data, int $utilisateurId): Bon
    {
        $type = TypeBon::from((int) $data['type']);

        if (empty($data['lignes'])) {
            throw new RuntimeException("Un bon doit comporter au moins une ligne.");
        }

        if ($type === TypeBon::TRANSFERT) {
            if (empty($data['magasin_destination_id'])) {
                throw new RuntimeException("Un transfert doit préciser le magasin de destination.");
            }
            if ((int) $data['magasin_id'] === (int) $data['magasin_destination_id']) {
                throw new RuntimeException("Le magasin source et destination doivent être différents.");
            }
        }

        return DB::transaction(function () use ($data, $type, $utilisateurId) {
            $anneeId = app(AnneeScolaireContext::class)->id();

            $bon = $this->bons->create([
                'reference'              => $this->bons->genererReference($type),
                'date_bon'               => $data['date_bon'] ?? now()->toDateString(),
                'type'                   => $type->value,
                'magasin_id'             => $data['magasin_id'],
                'magasin_destination_id' => $type === TypeBon::TRANSFERT ? $data['magasin_destination_id'] : null,
                'nom_responsable'        => $data['nom_responsable'] ?? null,
                'nom_magasinier'         => $data['nom_magasinier'] ?? null,
                'motif'                  => $data['motif'] ?? null,
                'commentaire'            => $data['commentaire'] ?? null,
                'utilisateur_id'         => $utilisateurId,
                'annee_id'               => $anneeId,
            ]);

            // Lignes
            foreach ($data['lignes'] as $ligne) {
                DetailBon::create([
                    'bon_id'        => $bon->id,
                    'produit_id'    => $ligne['produit_id'],
                    'libelle'       => $ligne['libelle'] ?? null,
                    'quantite'      => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'] ?? null,
                    'montant'       => isset($ligne['prix_unitaire'])
                        ? round($ligne['quantite'] * $ligne['prix_unitaire'], 2)
                        : null,
                    'annee_id'      => $anneeId,
                ]);
            }

            // Application immédiate sur le stock
            $this->appliquerSurStock($bon, $utilisateurId);

            return $bon->fresh('details');
        });
    }

    private function appliquerSurStock(Bon $bon, int $utilisateurId): void
    {
        $bon->load('details');

        foreach ($bon->details as $ligne) {
            switch ($bon->type) {
                case TypeBon::ENTREE:
                    $this->stocks->entrerEnStock(
                        produitId:     (int) $ligne->produit_id,
                        magasinId:     (int) $bon->magasin_id,
                        quantite:      (float) $ligne->quantite,
                        prixUnitaire:  (float) ($ligne->prix_unitaire ?? 0),
                        libelle:       "Entrée — {$bon->reference}",
                        utilisateurId: $utilisateurId,
                        sourceType:    $bon::class,
                        sourceId:      $bon->id,
                        bonId:         $bon->id,
                    );
                    break;

                case TypeBon::SORTIE:
                    $this->stocks->sortirDuStock(
                        produitId:     (int) $ligne->produit_id,
                        magasinId:     (int) $bon->magasin_id,
                        quantite:      (float) $ligne->quantite,
                        libelle:       "Sortie — {$bon->reference}",
                        utilisateurId: $utilisateurId,
                        motif:         $bon->motif,
                        bonId:         $bon->id,
                    );
                    break;

                case TypeBon::TRANSFERT:
                    $this->stocks->transferer(
                        produitId:       (int) $ligne->produit_id,
                        magasinSourceId: (int) $bon->magasin_id,
                        magasinDestId:   (int) $bon->magasin_destination_id,
                        quantite:        (float) $ligne->quantite,
                        bonId:           $bon->id,
                        utilisateurId:   $utilisateurId,
                        motif:           "Transfert — {$bon->reference}",
                    );
                    break;
            }
        }
    }
}