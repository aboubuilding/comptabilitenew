<?php

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Repositories\AnneeRepository;
use App\Domain\Scolarite\Types\StatutAnnee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Résolution de "l'année scolaire active" — extraite de AuthService, qui
 * n'a pas à connaître les règles de gestion d'une année scolaire. Appelée
 * au login par AuthService, qui se contente ensuite de pousser le
 * résultat dans Support\AnneeScolaireContext.
 *
 * Aucun accès direct au modèle Annee ici : toute lecture/écriture passe
 * par AnneeRepository, y compris la création de l'année par défaut.
 */
class AnneeService
{
    public function __construct(private AnneeRepository $annees)
    {
    }

    public function resolveActive(): Annee
    {
        return $this->annees->findOuverte()
            ?? $this->annees->findLastByDateRentree()
            ?? $this->createDefault();
    }

    private function createDefault(): Annee
    {
        $month     = Carbon::now()->month;
        $startYear = $month >= 9 ? Carbon::now()->year : Carbon::now()->year - 1;

        // 'etat' n'est pas passé explicitement : BaseRepository::create()
        // l'initialise déjà à ACTIF par défaut.
        $annee = $this->annees->create([
            'libelle'                      => "Année {$startYear}-" . ($startYear + 1),
            'date_rentree'                 => Carbon::create($startYear, 9, 1),
            'date_fin'                     => Carbon::create($startYear + 1, 8, 31),
            'date_ouverture_inscription'   => Carbon::create($startYear, 6, 1),
            'date_fermeture_reinscription' => Carbon::create($startYear + 1, 1, 31),
            'statut_annee'                 => StatutAnnee::Ouvert->value,
        ]);

        Log::info('Année scolaire par défaut créée automatiquement', [
            'annee_id' => $annee->id,
            'libelle'  => $annee->libelle,
        ]);

        return $annee;
    }

    /**
     * Clôturer une année scolaire — action métier distincte d'une simple
     * modification de formulaire (cahier des charges §4, 1.4 : "Clôturer
     * une année scolaire" est une action à part de "Créer une année").
     * Reste volontairement minimal pour l'instant (pas de garde-fou style
     * "impossible si des inscriptions sont encore en attente") : à
     * enrichir si le cahier des charges précise des conditions de blocage.
     */
    public function cloturer(Annee $annee): void
    {
        $this->annees->cloturer($annee->id);

        Log::info('Année scolaire clôturée', [
            'annee_id' => $annee->id,
            'libelle'  => $annee->libelle,
        ]);
    }
}