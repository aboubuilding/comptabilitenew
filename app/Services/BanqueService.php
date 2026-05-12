<?php

namespace App\Services;

use App\Repositories\Eloquent\BanqueRepository;
use App\Repositories\Eloquent\ChequeRepository;
use App\Repositories\Eloquent\PaiementRepository;
use Illuminate\Support\Facades\DB;

class BanqueService extends BaseService
{
    protected string $entityName = 'Banque';
    protected BanqueRepository $banqueRepo;
    protected ChequeRepository $chequeRepo;
    protected PaiementRepository $paiementRepo;

    public function __construct(
        BanqueRepository $banqueRepo,
        ChequeRepository $chequeRepo,
        PaiementRepository $paiementRepo
    ) {
        parent::__construct($banqueRepo);
        $this->banqueRepo = $banqueRepo;
        $this->chequeRepo = $chequeRepo;
        $this->paiementRepo = $paiementRepo;
    }

    /**
     * Récupère toutes les banques avec leurs statistiques (montants, nombres)
     */
    public function getAllWithStats(): array
    {
        $banques = $this->banqueRepo->activeQuery()
            ->withCount([
                'cheques as cheques_recus'
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
                        ->where('cheques.statut', 1)
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

        return $this->formatResponse(true, 'Liste des banques avec statistiques', $banques);
    }

    /**
     * Récupère toutes les banques pour les selects (dropdown)
     * Surcharge la méthode parent pour le format.
     */
    public function getForSelect(array $filters = [], string $labelField = 'nom', string $valueField = 'id'): array
    {
        $items = $this->banqueRepo->activeQuery()
            ->select($valueField, $labelField)
            ->orderBy($labelField)
            ->get()
            ->map(fn($item) => ['value' => $item->$valueField, 'label' => $item->$labelField]);

        return $this->formatResponse(true, '', $items);
    }

    /**
     * Trouve une banque par son ID (retour formaté)
     */
    public function find(int $id): array
    {
        try {
            $banque = $this->banqueRepo->findOrFail($id);
            return $this->formatResponse(true, '', $banque);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Banque introuvable');
        }
    }

    /**
     * Vérifie si la banque a des chèques liés (pour empêcher suppression)
     */
    public function hasRelatedCheques(int $id): bool
    {
        return $this->chequeRepo->activeQuery()
            ->where('banque_id', $id)
            ->exists();
    }
}
