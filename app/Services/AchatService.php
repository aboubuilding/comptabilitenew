<?php
namespace App\Services;

use App\Models\Achat;
use App\Models\DetailAchat;
use App\Models\Produit;
use App\Models\StockMouvement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AchatService
{
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
        $query = Achat::with(['fournisseur', 'details.produit'])
            ->where('annee_id', $anneeId)
            ->where('etat', 1);

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

        $aggregates = [
            'total_depenses' => (clone $query)->sum('montant_total'),
            'nombre_achats'  => (clone $query)->count(),
            'total_cantine'  => (clone $query)->where('type_achat', 1)->sum('montant_total'),
            'total_boutique' => (clone $query)->where('type_achat', 2)->sum('montant_total'),
        ];

        return [
            'data'       => $data,
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $achats->currentPage(),
                'last_page'    => $achats->lastPage(),
                'per_page'     => $achats->perPage(),
                'total'        => $achats->total(),
            ]
        ];
    }

    /**
     * Créer un achat (avec plusieurs produits)
     */
    public function createAchat(array $data): Achat
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            throw new \Exception("Année scolaire non définie en session.");
        }

        DB::beginTransaction();
        try {
            // 1. Créer l'entête de l'achat
            $achat = Achat::create([
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
                'montant_total'    => 0, // sera calculé
            ]);

            $totalGeneral = 0;
            foreach ($data['produits'] as $item) {
                $montantLigne = $item['quantite'] * $item['prix_unitaire'];
                $totalGeneral += $montantLigne;

                DetailAchat::create([
                    'achat_id'      => $achat->id,
                    'produit_id'    => $item['produit_id'],
                    'annee_id'      => $anneeId,
                    'quantite'      => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'montant_achat' => $montantLigne,
                    'etat'          => 1,
                ]);

                // Mise à jour du stock du produit (entrée)
                $produit = Produit::find($item['produit_id']);
                if ($produit) {
                    $produit->quantite_stock += $item['quantite'];
                    $produit->save();

                    // Traçabilité : mouvement de stock
                    StockMouvement::create([
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

            $achat->montant_total = $totalGeneral;
            $achat->save();

            DB::commit();
            return $achat;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour les statuts (paiement/livraison) d'un achat
     */
    public function updateAchatStatus(int $id, array $data): Achat
    {
        $achat = Achat::findOrFail($id);
        if (isset($data['statut_paiement'])) {
            $achat->statut_paiement = $data['statut_paiement'];
        }
        if (isset($data['statut_livraison'])) {
            $achat->statut_livraison = $data['statut_livraison'];
        }
        if (isset($data['commentaire'])) {
            $achat->commentaire = $data['commentaire'];
        }
        $achat->save();
        return $achat;
    }

    /**
     * Annuler un achat (soft delete / annulation logique)
     */
    public function deleteAchat(int $id): void
    {
        $achat = Achat::findOrFail($id);
        // Optionnel : on pourrait inverser les mouvements de stock
        // Mais on préfère garder la traçabilité. On se contente de passer etat=0
        $achat->etat = 0;
        $achat->save();
    }

    /**
     * Récupérer un achat avec ses détails
     */
    public function getAchat(int $id): Achat
    {
        return Achat::with(['fournisseur', 'details.produit'])->findOrFail($id);
    }
}