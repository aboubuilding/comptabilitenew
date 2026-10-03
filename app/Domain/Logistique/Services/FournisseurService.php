<?php

namespace App\Domain\Logistique\Services;

use App\Domain\Logistique\DTO\FournisseurData;
use App\Domain\Logistique\Models\Fournisseur;
use App\Domain\Logistique\Repositories\FournisseurRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FournisseurService
{
    public function __construct(
        private FournisseurRepository $fournisseurs,
    ) {}

    public function creer(FournisseurData $data): Fournisseur
    {
        $this->verifierUnicite($data->raisonSociale);

        return DB::transaction(fn () => $this->fournisseurs->create([
            'raison_social'     => $data->raisonSociale,
            'nom_contact'       => $data->nomContact,
            'telephone_contact' => $data->telephoneContact,
            'email_contact'     => $data->emailContact,
            'adresse'           => $data->adresse,
            'nif'               => $data->nif,
            'rccm'              => $data->rccm,
        ]));
    }

    public function modifier(int $id, FournisseurData $data): Fournisseur
    {
        $fournisseur = $this->fournisseurs->findOrFail($id);

        $this->verifierUnicite($data->raisonSociale, ignoreId: $id);

        $this->fournisseurs->update($id, [
            'raison_social'     => $data->raisonSociale,
            'nom_contact'       => $data->nomContact,
            'telephone_contact' => $data->telephoneContact,
            'email_contact'     => $data->emailContact,
            'adresse'           => $data->adresse,
            'nif'               => $data->nif,
            'rccm'              => $data->rccm,
        ]);

        return $fournisseur->refresh();
    }

    /**
     * Suppression logique — refusée si des achats existent.
     * (tolérant : si la table `achats` n'existe pas encore, on autorise
     * la suppression.)
     */
    public function supprimer(int $id): void
    {
        $fournisseur = $this->fournisseurs->findOrFail($id);

        if (Schema::hasTable('achats') && $fournisseur->achats()->where('etat', 1)->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce fournisseur : des achats y sont rattachés. " .
                "Désactivez-le à la place."
            );
        }

        $this->fournisseurs->delete($id);
    }

    /* ================================================================
       Internals
       ================================================================ */

    private function verifierUnicite(string $raisonSociale, ?int $ignoreId = null): void
    {
        $existe = $this->fournisseurs->activeQuery()
            ->where('raison_social', $raisonSociale)
            ->when($ignoreId, fn ($q, $v) => $q->where('id', '!=', $v))
            ->exists();

        if ($existe) {
            throw new RuntimeException(
                "Un fournisseur avec la raison sociale « {$raisonSociale} » existe déjà."
            );
        }
    }
}