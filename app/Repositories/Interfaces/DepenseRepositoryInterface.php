<?php
namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface DepenseRepositoryInterface extends BaseRepositoryInterface
{
    /** Valide une demande de dépense (passe à l'étape de paiement possible) */
    public function valider(int $id, int $validateurId): bool;

    /** Retourne le total déjà payé via des mouvements de type SORTIE */
    public function getMontantPaye(int $id): float;

    /** Liste les dépenses filtrées par statut (ex: 1=en_attente, 2=validee, 3=payee) */
    public function getDepensesByStatut(int $statut, ?int $anneeId = null): Collection;
    //                                                    ↑ Changé de string à int
}