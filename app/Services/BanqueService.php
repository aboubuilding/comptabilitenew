<?php

namespace App\Services;

use App\Models\Banque;
use App\Models\Cheque;
use App\Models\Paiement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BanqueService
{
    /**
     * Récupère toutes les banques avec leurs statistiques (montants, nombres)
     */
    public function getAllWithStats(): Collection
    {
        return Banque::where('etat', 1)
            ->withCount([
                'cheques as cheques_recus'   // nombre total de chèques pour cette banque
            ])
            ->withSum([
                'cheques as montant_total_cheques' => function ($query) {
                    $query->join('paiements', 'cheques.id', '=', 'paiements.cheque_id')
                          ->where('paiements.etat', 1);
                }
            ], 'paiements.montant')
            ->withSum([
                'cheques as montant_cheques_valides' => function ($query) {
                    $query->join('paiements', 'cheques.id', '=', 'paiements.cheque_id')
                          ->where('cheques.statut', 1)      // 1 = validé/encaissé
                          ->where('paiements.etat', 1);
                }
            ], 'paiements.montant')
            ->withCount([
                'cheques as cheques_valides' => function ($query) {
                    $query->where('statut', 1);
                }
            ])
            ->get()
            ->map(function ($banque) {
                return [
                    'id'                     => $banque->id,
                    'nom'                    => $banque->nom,
                    'montant_total_cheques'  => $banque->montant_total_cheques ?? 0,
                    'montant_cheques_valides'=> $banque->montant_cheques_valides ?? 0,
                    'cheques_recus'          => $banque->cheques_recus ?? 0,
                    'cheques_valides'        => $banque->cheques_valides ?? 0,
                    'etat'                   => $banque->etat,
                ];
            });
    }

    /**
     * Récupère toutes les banques pour les selects (dropdown)
     */
    public function getForSelect(): Collection
    {
        return Banque::where('etat', 1)
            ->select('id', 'nom')
            ->orderBy('nom')
            ->get();
    }

    /**
     * Récupère une banque par son ID
     */
    public function find(int $id): Banque
    {
        return Banque::findOrFail($id);
    }

    /**
     * Crée une nouvelle banque
     */
    public function store(array $data): Banque
    {
        return Banque::create($data);
    }

    /**
     * Met à jour une banque
     */
    public function update(int $id, array $data): Banque
    {
        $banque = $this->find($id);
        $banque->update($data);
        return $banque;
    }

    /**
     * Supprime (soft delete) une banque
     */
    public function delete(int $id): bool
    {
        $banque = $this->find($id);
        return $banque->delete();
    }

    /**
     * Vérifie si la banque a des chèques liés (pour empêcher suppression)
     */
    public function hasRelatedCheques(int $id): bool
    {
        return Cheque::where('banque_id', $id)->exists();
    }
}