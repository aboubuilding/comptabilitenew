<?php
namespace App\Services;

use App\Repositories\Eloquent\AchatRepository;
use App\Repositories\Eloquent\DetailAchatRepository;
use App\Repositories\Eloquent\ProduitRepository;
use App\Repositories\Eloquent\MouvementStockRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AchatService extends BaseService
{
    protected AchatRepository $achatRepo;
    protected DetailAchatRepository $detailRepo;
    protected ProduitRepository $produitRepo;
    protected MouvementStockRepository $mouvementRepo;

    public function __construct(
        AchatRepository $achatRepo,
        DetailAchatRepository $detailRepo,
        ProduitRepository $produitRepo,
        MouvementStockRepository $mouvementRepo
    ) {
        parent::__construct($achatRepo);
        $this->achatRepo = $achatRepo;
        $this->detailRepo = $detailRepo;
        $this->produitRepo = $produitRepo;
        $this->mouvementRepo = $mouvementRepo;
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des achats avec filtres (dates, type, fournisseur, etc.)
     */
    public function listAchats(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();
        $query = $this->achatRepo->activeQuery()
            ->with(['fournisseur', 'details.produit'])
            ->where('annee_id', $anneeId);

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_achat', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_achat', '<=', $filters['date_fin']);
        }
        if (isset($filters['type_achat']) && in_array($filters['type_achat'], [1,2])) {
            $query->where('type_achat', $filters['type_achat']);
        }
        if (!empty($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }
        if (isset($filters['statut_paiement']) && in_array($filters['statut_paiement'], [0,1,2])) {
            $query->where('statut_paiement', $filters['statut_paiement']);
        }

        $perPage = $filters['per_page'] ?? 15;
        $achats = $query->orderBy('date_achat', 'desc')->paginate($perPage);

        $data = $achats->map(function ($achat) {
            return [
                'id'               => $achat->id,
                'date_achat'       => $achat->date_achat->format('Y-m-d'),
                'reference'        => $achat->reference,
                'fournisseur'      => $achat->fournisseur?->nom,
                'montant_total'    => $achat->montant_total,
                'type_achat'       => $achat->type_achat,
                'type_achat_label' => $achat->type_achat == 1 ? 'Cantine' : 'Boutique',
                'statut_paiement'  => $achat->statut_paiement,
                'statut_livraison' => $achat->statut_livraison,
                'commentaire'      => $achat->commentaire,
                'details'          => $achat->details->map(fn($d) => [
                    'produit'        => $d->produit?->libelle,
                    'quantite'       => $d->quantite,
                    'prix_unitaire'  => $d->prix_unitaire,
                    'montant_achat'  => $d->montant_achat,
                ]),
            ];
        });

        // Pour les aggregates, on clone la requête originale
        $baseQuery = $this->achatRepo->activeQuery()
            ->where('annee_id', $anneeId);
        // Appliquer les mêmes filtres (sans pagination)
        if (!empty($filters['date_debut'])) {
            $baseQuery->whereDate('date_achat', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $baseQuery->whereDate('date_achat', '<=', $filters['date_fin']);
        }
        if (isset($filters['type_achat']) && in_array($filters['type_achat'], [1,2])) {
            $baseQuery->where('type_achat', $filters['type_achat']);
        }
        if (!empty($filters['fournisseur_id'])) {
            $baseQuery->where('fournisseur_id', $filters['fournisseur_id']);
        }

        $aggregates = [
            'total_depenses' => (clone $baseQuery)->sum('montant_total'),
            'nombre_achats'  => (clone $baseQuery)->count(),
            'total_cantine'  => (clone $baseQuery)->where('type_achat', 1)->sum('montant_total'),
            'total_boutique' => (clone $baseQuery)->where('type_achat', 2)->sum('montant_total'),
        ];

        return $this->formatResponse(true, 'Liste des achats', $data, [
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $achats->currentPage(),
                'last_page'    => $achats->lastPage(),
                'per_page'     => $achats->perPage(),
                'total'        => $achats->total(),
            ]
        ]);
    }

    /**
     * Créer un achat (avec plusieurs produits)
     */
    public function createAchat(array $data): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(false, 'Année scolaire non définie en session.');
        }

        DB::beginTransaction();
        try {
            // 1. Créer l'entête de l'achat
            $achatData = [
                'date_achat'       => $data['date_achat'],
                'fournisseur_id'   => $data['fournisseur_id'] ?? null,
                'nom_acheteur'     => $data['nom_acheteur'] ?? null,
                'reference'        => $data['reference'] ?? null,
                'bon_commande'     => $data['bon_commande'] ?? null,
                'commentaire'      => $data['commentaire'] ?? null,
                'annee_id'         => $anneeId,
                'type_achat'       => $data['type_achat'],
                'statut_paiement'  => $data['statut_paiement'] ?? 0,
                'statut_livraison' => $data['statut_livraison'] ?? 0,
                'etat'             => 1,
                'montant_total'    => 0,
            ];
            $achat = $this->achatRepo->create($achatData);

            $totalGeneral = 0;
            foreach ($data['produits'] as $item) {
                $montantLigne = $item['quantite'] * $item['prix_unitaire'];
                $totalGeneral += $montantLigne;

                $this->detailRepo->create([
                    'achat_id'      => $achat->id,
                    'produit_id'    => $item['produit_id'],
                    'annee_id'      => $anneeId,
                    'quantite'      => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'montant_achat' => $montantLigne,
                    'etat'          => 1,
                ]);

                // Mise à jour du stock du produit
                $produit = $this->produitRepo->find($item['produit_id']);
                if ($produit) {
                    $produit->quantite_stock += $item['quantite'];
                    $produit->save();

                    // Traçabilité : mouvement de stock
                    $this->mouvementRepo->create([
                        'produit_id'     => $produit->id,
                        'type'           => 'entree',
                        'quantite'       => $item['quantite'],
                        'motif'          => 'Achat N°' . $achat->id,
                        'reference_id'   => $achat->id,
                        'utilisateur_id' => Auth::id(),
                        'date_mouvement' => $data['date_achat'],
                    ]);
                }
            }

            $this->achatRepo->update($achat->id, ['montant_total' => $totalGeneral]);

            DB::commit();
            return $this->formatResponse(true, 'Achat créé avec succès', $achat);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->formatResponse(false, 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour les statuts (paiement/livraison) d'un achat
     */
    public function updateAchatStatus(int $id, array $data): array
    {
        try {
            $achat = $this->achatRepo->findOrFail($id);
            $updateData = [];
            if (isset($data['statut_paiement'])) {
                $updateData['statut_paiement'] = $data['statut_paiement'];
            }
            if (isset($data['statut_livraison'])) {
                $updateData['statut_livraison'] = $data['statut_livraison'];
            }
            if (isset($data['commentaire'])) {
                $updateData['commentaire'] = $data['commentaire'];
            }
            if (!empty($updateData)) {
                $this->achatRepo->update($id, $updateData);
            }
            return $this->formatResponse(true, 'Statut mis à jour', $achat);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Achat introuvable');
        }
    }

    /**
     * Annuler un achat (soft delete / annulation logique)
     */
    public function deleteAchat(int $id): array
    {
        try {
            $this->achatRepo->findOrFail($id);
            $this->achatRepo->update($id, ['etat' => 0]);
            return $this->formatResponse(true, 'Achat annulé');
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Achat introuvable');
        }
    }

    /**
     * Récupérer un achat avec ses détails
     */
    public function getAchat(int $id): array
    {
        try {
            $achat = $this->achatRepo->activeQuery()
                ->with(['fournisseur', 'details.produit'])
                ->findOrFail($id);
            return $this->formatResponse(true, '', $achat);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Achat introuvable');
        }
    }
}
