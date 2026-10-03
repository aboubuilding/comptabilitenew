<?php

namespace Tests\Domain\Rh;

use App\Domain\Rh\DTO\EmployeData;
use App\Domain\Rh\Models\Employe;
use App\Domain\Rh\Services\EmployeService;
use Illuminate\Support\Facades\DB;
use Tests\Domain\DomainTestCase;

/**
 * Jeux de données communs au menu 5 — Ressources humaines.
 */
abstract class RhTestCase extends DomainTestCase
{
    protected function employeData(array $donnees = []): EmployeData
    {
        $d = $donnees + [
            'nom_prenom'    => 'AMOUZOU Kossi',
            'numero_cnss'   => null,
            'salaire'       => 150000,
            'nbre_enfant'   => 0,
            'profession_id' => null,
            'mode_paiement' => 'billette',
            'banque'        => null,
            'iban'          => null,
            'irpp_mode'     => 'auto',
        ];

        return new EmployeData(
            nomPrenom: $d['nom_prenom'],
            numeroCnss: $d['numero_cnss'],
            quartier: null,
            ville: 'Lomé',
            telephone: '90444444',
            dateEmbauche: '2024-09-01',
            dateNaissance: null,
            lieuNaissance: null,
            email: null,
            genre: 'M',
            situationFamiliale: null,
            dateFinContrat: null,
            isCnss: true,
            isAmu: false,
            nbreEnfant: $d['nbre_enfant'],
            isPensionValidite: false,
            tauxPensionValidite: null,
            naturePensionValidite: null,
            salaire: $d['salaire'],
            professionId: $d['profession_id'],
            modePaiement: $d['mode_paiement'],
            banque: $d['banque'],
            iban: $d['iban'],
            titulaireCompte: null,
            irppMode: $d['irpp_mode'],
        );
    }

    protected function creerEmploye(array $donnees = []): Employe
    {
        return app(EmployeService::class)->creer($this->employeData($donnees));
    }

    protected function creerPeriode(string $libelle = 'Septembre 2026'): int
    {
        return DB::table('periodes')->insertGetId([
            'libelle'    => $libelle,
            'date_debut' => '2026-09-01',
            'date_fin'   => '2026-09-30',
            'annee_id'   => $this->annee->id,
            'etat'       => 1,
        ]);
    }
}
