<?php
namespace App\Services;

use App\Repositories\Interfaces\DepenseRepositoryInterface;
use App\Repositories\Interfaces\MouvementRepositoryInterface;
use App\Types\TypeMouvement;
use App\Types\StatutMouvement;
use Illuminate\Support\Facades\DB;

class ReportingService extends BaseService
{
    // On choisit DepenseRepositoryInterface comme repo principal (artificiel)
    protected string $entityName = 'Reporting';
    protected MouvementRepositoryInterface $mouvementRepo;

    public function __construct(
        DepenseRepositoryInterface $depenseRepo,
        MouvementRepositoryInterface $mouvementRepo
    ) {
        parent::__construct($depenseRepo); // repo principal non utilisé
        $this->mouvementRepo = $mouvementRepo;
    }

    // Désactiver les méthodes CRUD non pertinentes
    public function store(array $validatedData): array
    {
        throw new \LogicException('Méthode non supportée pour les reporting.');
    }

    public function update(int $id, array $validatedData): array
    {
        throw new \LogicException('Méthode non supportée pour les reporting.');
    }

    public function destroy(int $id): array
    {
        throw new \LogicException('Méthode non supportée pour les reporting.');
    }

    // ... autres méthodes de reporting inchangées ...
}
