<?php
namespace App\Services;

use App\Models\Achat;
use App\Models\DetailAchat;
use App\Models\Produit;
use App\Models\Magasin;
use App\Models\StockActuel;
use App\Models\MouvementStock;
use Illuminate\Support\Facades\DB;

class AchatBoutiqueService
{
    const TYPE_ACHAT_BOUTIQUE = 2;
    const MAGASIN_ENTREPOT_DEFAUT = 1; // à ajuster selon votre vrai ID

    public function listAchats(array $filters = [])
    {
        $query = Achat::with(['fournisseur'])
            ->where('type_achat', self::TYPE_ACHAT_BOUTIQUE)
            ->where('etat', 1);

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_achat', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_achat', '<=', $filters['date_fin']);
        }
        if (!empty($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }
        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', '%' . $filters['reference'] . '%');
        }

        $perPage = $filters['per_page'] ?? 15;
        $achats = $query->orderBy('date_achat', 'desc')->paginate($perPage);

        $data = $achats->map(fn($a) => [
            'id'           => $a->id,
            'date_achat'   => $a->date_achat->format('Y-m-d'),
            'reference'    => $a->reference,
            'fournisseur'  => $a->fournisseur?->raison_social,
            'montant_total'=> $a->montant_total,
            'statut_paiement' => $a->statut_paiement,
            'statut_livraison'=> $a->statut_livraison,
            'commentaire'  => $a->commentaire,
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $achats->currentPage(),
                'last_page'    => $achats->lastPage(),
                'per_page'     => $achats->perPage(),
                'total'        => $achats->total(),
            ]
        ];
    }

    /**
     * Créer un achat boutique : stock dans l'entrepôt principal
     */
    public function createAchat(array $data): Achat
    {
        $anneeId = session('LoginUser.annee_id') ?? 1;
        DB::beginTransaction();
        try {
            // Entête achat
            $achat = Achat::create([
                'date_achat'       => $data['date_achat'],
                'fournisseur_id'   => $data['fournisseur_id'] ?? null,
                'reference'        => $data['reference'] ?? null,
                'commentaire'      => $data['commentaire'] ?? null,
                'montant_total'    => 0,
                'annee_id'         => $anneeId,
                'type_achat'       => self::TYPE_ACHAT_BOUTIQUE,
                'statut_paiement'  => $data['statut_paiement'] ?? 0,
                'statut_livraison' => $data['statut_livraison'] ?? 0,
                'etat'             => 1,
            ]);

            $total = 0;
            foreach ($data['details'] as $item) {
                $produit = Produit::findOrFail($item['produit_id']);
                // Quantité en unité de base
                $quantiteBase = $produit->convertirVersBase($item['quantite'], $item['unite']);

                $detail = DetailAchat::create([
                    'achat_id'      => $achat->id,
                    'produit_id'    => $item['produit_id'],
                    'quantite'      => $item['quantite'],
                    'unite'         => $item['unite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                ]);
                $total += $detail->montant;

                // Mise à jour du stock dans l'entrepôt
                $this->ajouterStockEntrepot($produit->id, $quantiteBase, $achat->id);
            }

            $achat->montant_total = $total;
            $achat->save();
            DB::commit();
            return $achat;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function ajouterStockEntrepot(int $produitId, float $quantiteBase, int $achatId): void
    {
        $entrepotId = self::MAGASIN_ENTREPOT_DEFAUT;

        // Mise à jour stock_actuel
        $stock = StockActuel::firstOrNew([
            'produit_id' => $produitId,
            'magasin_id' => $entrepotId,
        ]);
        $stock->quantite += $quantiteBase;
        $stock->save();

        // Mouvement entrée
        MouvementStock::create([
            'produit_id'     => $produitId,
            'magasin_id'     => $entrepotId,
            'type'           => 'entree',
            'quantite'       => $quantiteBase,
            'motif'          => "Achat boutique N°{$achatId}",
            'reference_id'   => $achatId,
            'utilisateur_id' => auth()->id(),
            'date_mouvement' => now(),
        ]);
    }

    /**
     * Mettre à jour le statut paiement ou livraison
     */
    public function updateStatut($id, $field, $value)
    {
        $achat = Achat::findOrFail($id);
        if (!in_array($field, ['statut_paiement', 'statut_livraison'])) {
            throw new \Exception('Champ non autorisé');
        }
        $achat->$field = $value;
        $achat->save();
        return $achat;
    }

    /**
     * Annuler l'achat (soft delete)
     */
    public function deleteAchat($id)
    {
        $achat = Achat::findOrFail($id);
        $achat->etat = 0;
        $achat->save();
        // Optionnel : inverser les mouvements de stock
    }
}