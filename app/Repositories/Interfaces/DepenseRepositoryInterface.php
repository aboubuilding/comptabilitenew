<?php
namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface DepenseRepositoryInterface extends BaseRepositoryInterface
{
    /** Valide une demande de dépense (passe à l'étape de paiement possible) */
    public function valider(int $id, int $validateurId): bool;

    /** Retourne le total déjà payé via des mouvements de type SORTIE */
    public function getMontantPaye(int $id): float;



    /** Liste les dépenses filtrées par statut (ex: 'en_attente', 'validee', 'payee') */
    public function getDepensesByStatut(string $statut, ?int $anneeId = null): Collection;
}