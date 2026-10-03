<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\CentreAnalytiqueData;
use App\Domain\Finances\Models\CentreAnalytique;
use App\Domain\Finances\Repositories\CentreAnalytiqueRepository;
use Illuminate\Support\Facades\DB;

class CentreAnalytiqueService
{
    public function __construct(
        private CentreAnalytiqueRepository $centres,
    ) {}

    public function creer(CentreAnalytiqueData $data): CentreAnalytique
    {
        return DB::transaction(fn () => $this->centres->create([
            'code'           => $data->code,
            'libelle'        => $data->libelle,
            'description'    => $data->description,
            'type'           => $data->type,
            'responsable_id' => $data->responsableId,
        ]));
    }

    public function modifier(int $id, CentreAnalytiqueData $data): CentreAnalytique
    {
        $this->centres->update($id, [
            'code'           => $data->code,
            'libelle'        => $data->libelle,
            'description'    => $data->description,
            'type'           => $data->type,
            'responsable_id' => $data->responsableId,
        ]);

        return $this->centres->findOrFail($id);
    }

    public function supprimer(int $id): void
    {
        $centre = $this->centres->findOrFail($id);

        if ($centre->imputations()->exists()) {
            throw new \RuntimeException(
                "Impossible de supprimer ce centre : des écritures y sont déjà imputées."
            );
        }

        $this->centres->delete($id);
    }
}