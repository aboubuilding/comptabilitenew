<?php

namespace App\Repositories\Interfaces;

/**
 * Interface AbonnementBusRepositoryInterface
 *
 * Contrat pour le repository des abonnements bus.
 * Hérite de toutes les méthodes CRUD de base (find, create, update, delete, etc.).
 */
interface AbonnementBusRepositoryInterface extends BaseRepositoryInterface
{
    // ✅ Vide : hérite de tout le CRUD de BaseRepositoryInterface
    // Vous pouvez ajouter ici des méthodes spécifiques aux abonnements bus,
    // par exemple :
    // public function findByInscription(int $inscriptionId): ?\App\Models\AbonnementBus;
    // public function getActifsForYear(int $anneeId): \Illuminate\Support\Collection;
}
