<?php
namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;


interface MouvementRepositoryInterface extends BaseRepositoryInterface
{
    public function enregistrer(array $data): Model;
    public function valider(int $id): bool; // ✅ Devient l'action définitive
    public function rejeter(int $id, ?string $motif = null): bool;
    
    public function getSoldeCaisse(int $caisseId, float $soldeInitial = 0): float;
    public function getSoldeByType(int $caisseId, int $type): float;
    public function genererReference(): string;
    public function getMouvementsByStatut(int $caisseId, int $statut): Collection;
}