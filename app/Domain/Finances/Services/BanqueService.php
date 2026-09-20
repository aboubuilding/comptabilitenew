<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\BanqueData;
use App\Domain\Finances\Models\Banque;
use App\Domain\Finances\Repositories\BanqueRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BanqueService
{
    public function __construct(
        private BanqueRepository $banques,
    ) {}

    public function creer(BanqueData $data): Banque
    {
        return DB::transaction(fn () => $this->banques->create([
            'nom' => $data->nom,
        ]));
    }

    public function modifier(int $banqueId, BanqueData $data): Banque
    {
        $banque = $this->banques->findOrFail($banqueId);

        if ($banque->cheques()->exists() && $banque->nom !== $data->nom) {
            // Simple garde-fou : on peut renommer, mais on prévient si des
            // chèques y sont déjà rattachés (utile pour un futur audit).
            // Aucune exception ici : le changement de nom reste permis.
        }

        $this->banques->update($banque->id, ['nom' => $data->nom]);

        return $banque->refresh();
    }

    /** Suppression logique — refusée si des chèques y sont rattachés. */
    public function supprimer(int $banqueId): void
    {
        $banque = $this->banques->findOrFail($banqueId);

        if ($banque->cheques()->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer cette banque : des chèques y sont rattachés."
            );
        }

        $this->banques->delete($banque->id);
    }
}