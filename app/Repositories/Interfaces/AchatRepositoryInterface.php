<?php

namespace App\Repositories\Interfaces;

/**
 * Interface AchatRepositoryInterface
 *
 * Contrat pour le repository des achats.
 * Hérite de toutes les méthodes CRUD de base (find, create, update, delete, etc.).
 */
interface AchatRepositoryInterface extends BaseRepositoryInterface
{
    // ✅ Vide : hérite de tout le CRUD de BaseRepositoryInterface
    // Vous pouvez ajouter ici des méthodes spécifiques aux achats, par exemple :
    // public function getByFournisseur(int $fournisseurId): \Illuminate\Support\Collection;
    // public function getByAnnee(int $anneeId): \Illuminate\Support\Collection;
    // public function getByType(int $typeAchat): \Illuminate\Support\Collection;
}
