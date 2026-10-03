<?php

namespace App\Domain\Rh\Services;

use App\Domain\Rh\DTO\ProfessionData;
use App\Domain\Rh\Models\Profession;
use App\Domain\Rh\Repositories\ProfessionRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProfessionService
{
    public function __construct(
        private ProfessionRepository $professions,
    ) {}

    public function creer(ProfessionData $data): Profession
    {
        $this->verifierUnicite($data->libelle);

        return DB::transaction(fn () => $this->professions->create([
            'libelle'      => $data->libelle,
            'salaire_base' => $data->salaireBase,
        ]));
    }

    public function modifier(int $id, ProfessionData $data): Profession
    {
        $profession = $this->professions->findOrFail($id);

        $this->verifierUnicite($data->libelle, ignoreId: $id);

        $this->professions->update($id, [
            'libelle'      => $data->libelle,
            'salaire_base' => $data->salaireBase,
        ]);

        return $profession->refresh();
    }

    public function supprimer(int $id): void
    {
        $profession = $this->professions->findOrFail($id);

        if ($profession->employes()->where('etat', 1)->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer cette profession : des employés y sont rattachés."
            );
        }

        $this->professions->delete($id);
    }

    private function verifierUnicite(string $libelle, ?int $ignoreId = null): void
    {
        $existe = $this->professions->activeQuery()
            ->where('libelle', $libelle)
            ->when($ignoreId, fn ($q, $v) => $q->where('id', '!=', $v))
            ->exists();

        if ($existe) {
            throw new RuntimeException("Une profession avec ce libellé existe déjà.");
        }
    }
}