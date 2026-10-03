<?php

namespace App\Domain\Rh\Services;

use App\Domain\Rh\DTO\EmployeData;
use App\Domain\Rh\Models\Employe;
use App\Domain\Rh\Repositories\EmployeRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeService
{
    public function __construct(
        private EmployeRepository $employes,
    ) {}

    public function creer(EmployeData $data): Employe
    {
        if (empty($data->nomPrenom)) {
            throw new RuntimeException("Le nom et prénom sont obligatoires.");
        }
        if ($data->salaire < 0) {
            throw new RuntimeException("Le salaire ne peut pas être négatif.");
        }

        // Unicité du numéro CNSS si fourni
        if ($data->numeroCnss) {
            $existe = $this->employes->activeQuery()
                ->where('numero_cnss', $data->numeroCnss)
                ->exists();
            if ($existe) {
                throw new RuntimeException("Ce numéro CNSS est déjà utilisé par un autre employé.");
            }
        }

        return DB::transaction(fn () => $this->employes->create([
            'nom_prenom'              => $data->nomPrenom,
            'numero_cnss'             => $data->numeroCnss,
            'quartier'                => $data->quartier,
            'ville'                   => $data->ville,
            'telephone'               => $data->telephone,
            'date_embauche'           => $data->dateEmbauche,
            'date_naissance'          => $data->dateNaissance,
            'lieu_naissance'          => $data->lieuNaissance,
            'email'                   => $data->email,
            'genre'                   => $data->genre,
            'situation_familiale'     => $data->situationFamiliale,
            'date_fin_contrat'        => $data->dateFinContrat,
            'is_cnss'                 => $data->isCnss ? 1 : 0,
            'is_amu'                  => $data->isAmu ? 1 : 0,
            'nbre_enfant'             => $data->nbreEnfant,
            'is_pension_validite'     => $data->isPensionValidite ? 1 : 0,
            'taux_pension_validite'   => $data->tauxPensionValidite,
            'nature_pension_validite' => $data->naturePensionValidite,
            'salaire'                 => $data->salaire,
            'profession_id'           => $data->professionId,

            'mode_paiement'    => $data->modePaiement,
'banque'           => $data->banque,
'iban'             => $data->iban,
'titulaire_compte' => $data->titulaireCompte,
'irpp_mode' => $data->irppMode,
        ]));
    }

    public function modifier(int $id, EmployeData $data): Employe
    {
        $employe = $this->employes->findOrFail($id);

        if (empty($data->nomPrenom)) {
            throw new RuntimeException("Le nom et prénom sont obligatoires.");
        }

        // Unicité du CNSS (en ignorant l'employé courant)
        if ($data->numeroCnss) {
            $existe = $this->employes->activeQuery()
                ->where('numero_cnss', $data->numeroCnss)
                ->where('id', '!=', $id)
                ->exists();
            if ($existe) {
                throw new RuntimeException("Ce numéro CNSS est déjà utilisé par un autre employé.");
            }
        }

        $this->employes->update($id, [
            'nom_prenom'              => $data->nomPrenom,
            'numero_cnss'             => $data->numeroCnss,
            'quartier'                => $data->quartier,
            'ville'                   => $data->ville,
            'telephone'               => $data->telephone,
            'date_embauche'           => $data->dateEmbauche,
            'date_naissance'          => $data->dateNaissance,
            'lieu_naissance'          => $data->lieuNaissance,
            'email'                   => $data->email,
            'genre'                   => $data->genre,
            'situation_familiale'     => $data->situationFamiliale,
            'date_fin_contrat'        => $data->dateFinContrat,
            'is_cnss'                 => $data->isCnss ? 1 : 0,
            'is_amu'                  => $data->isAmu ? 1 : 0,
            'nbre_enfant'             => $data->nbreEnfant,
            'is_pension_validite'     => $data->isPensionValidite ? 1 : 0,
            'taux_pension_validite'   => $data->tauxPensionValidite,
            'nature_pension_validite' => $data->naturePensionValidite,
            'salaire'                 => $data->salaire,
            'profession_id'           => $data->professionId,

            'mode_paiement'    => $data->modePaiement,
'banque'           => $data->banque,
'iban'             => $data->iban,
'titulaire_compte' => $data->titulaireCompte,
'irpp_mode' => $data->irppMode,
        ]);

        return $employe->refresh();
    }

    /**
     * Suppression logique : on refuse si l'employé a une activité liée
     * (paie, assurance...). Pour l'instant on autorise la désactivation
     * pure — à durcir plus tard.
     */
    public function supprimer(int $id): void
    {
        $employe = $this->employes->findOrFail($id);

        $this->employes->delete($id);
    }
}