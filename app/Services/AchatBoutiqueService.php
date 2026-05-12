<?php

namespace App\Services;

use App\Models\Achat;
use App\Models\DetailAchat;
use App\Models\Produit;
use App\Models\StockActuel;
use App\Models\MouvementStock;
use App\Repositories\Eloquent\AchatRepository;
use App\Repositories\Eloquent\ProduitRepository;
use App\Repositories\Eloquent\StockActuelRepository;
use App\Repositories\Eloquent\MouvementStockRepository;
use Illuminate\Support\Facades\DB;

class AchatBoutiqueService extends BaseService
{
    // Constantes propres au service
    const TYPE_ACHAT_BOUTIQUE = 2;
    const MAGASIN_ENTREPOT_DEFAUT = 1; // à ajuster

    protected AchatRepository $achatRepo;
    protected ProduitRepository $produitRepo;
    protected StockActuelRepository $stockRepo;
    protected MouvementStockRepository $mouvementRepo;

    public function __construct(
        AchatRepository $achatRepo,
        ProduitRepository $produitRepo,
        StockActuelRepository $stockRepo,
        MouvementStockRepository $mouvementRepo
    ) {
        // On passe un repository au parent (optionnel, car BaseService attend un repo)
        // Ici on choisit d'appeler parent avec le repo principal (Achat)
        parent::__construct($achatRepo);

        $this->achatRepo = $achatRepo;
        $this->produitRepo = $produitRepo;
        $this->stockRepo = $stockRepo;
        $this->mouvementRepo = $mouvementRepo;
    }

    /**
     * Liste des achats boutique avec filtres et pagination
     */
    public function listAchats(array $filters = []): array
    {
        $query = $this->achatRepo->activeQuery()
            ->with(['fournisseur'])
            ->where('type_achat', self::TYPE_ACHAT_BOUTIQUE);

        // Filtres
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
            'id'               => $a->id,
            'date_achat'       => $a->date_achat->format('Y-m-d'),
            'reference'        => $a->reference,
            'fournisseur'      => $a->fournisseur?->raison_social,
            'montant_total'    => $a->montant_total,
            'statut_paiement'  => $a->statut_paiement,
            'statut_livraison' => $a->statut_livraison,
            'commentaire'      => $a->commentaire,
        ]);

        return $this->formatResponse(true, 'Liste des achats boutique', $data, [
            'pagination' => [
                'current_page' => $achats->currentPage(),
                'last_page'    => $achats->lastPage(),
                'per_page'     => $achats->perPage(),
                'total'        => $achats->total(),
            ]
        ]);
    }

    /**
     * Créer un achat boutique : stock dans l'entrepôt principal
     */
    public function createAchat(array $data): array
    {
        $anneeId = session('LoginUser.annee_id') ?? 1;

        DB::beginTransaction();
        try {
            // Création de l'entête achat
            $achatData = [
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
            ];

            $achat = $this->achatRepo->create($achatData);
            $total = 0;

            foreach ($data['details'] as $item) {
                $produit = $this->produitRepo->findOrFail($item['produit_id']);
                $quantiteBase = $produit->convertirVersBase($item['quantite'], $item['unite']);

                // Création du détail
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

            // Mise à jour du montant total
            $this->achatRepo->update($achat->id, ['montant_total' => $total]);

            DB::commit();
            return $this->formatResponse(true, 'Achat créé avec succès', $achat);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->formatResponse(false, 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Ajouter du stock dans l'entrepôt et enregistrer le mouvement
     */
    private function ajouterStockEntrepot(int $produitId, float $quantiteBase, int $achatId): void
    {
        $entrepotId = self::MAGASIN_ENTREPOT_DEFAUT;

        // Mise à jour stock_actuel
        $stock = $this->stockRepo->firstOrNew([
            'produit_id' => $produitId,
            'magasin_id' => $entrepotId,
        ]);
        $stock->quantite += $quantiteBase;
        $stock->save();

        // Mouvement entrée
        $this->mouvementRepo->create([
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
     * Mettre à jour le statut (paiement ou livraison) d'un achat
     */
    public function updateStatut(int $id, string $field, $value): array
    {
        if (!in_array($field, ['statut_paiement', 'statut_livraison'])) {
            return $this->formatResponse(false, 'Champ non autorisé');
        }

        try {
            $achat = $this->achatRepo->findOrFail($id);
            $this->achatRepo->update($id, [$field => $value]);
            return $this->formatResponse(true, 'Statut mis à jour', $achat);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Achat introuvable');
        }
    }

    /**
     * Annuler (soft delete) un achat
     */
    public function deleteAchat(int $id): array
    {
        try {
            $achat = $this->achatRepo->findOrFail($id);
            $this->achatRepo->update($id, ['etat' => 0]);
            // Optionnel : inverser les mouvements de stock
            return $this->formatResponse(true, 'Achat annulé', $achat);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Achat introuvable');
        }
    }
}
