<?php

namespace App\Services;

use App\Repositories\Interfaces\PeriodeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PeriodeService extends BaseService
{
    protected string $entityName = 'Période';
    protected array $defaultSelectFields = ['id', 'libelle', 'date_debut', 'date_fin', 'annee_id', 'etat'];

    public function __construct(PeriodeRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Récupère les périodes d'une année donnée, triées par date de début
     */
    public function getByAnnee(int $anneeId): Collection
    {
        return $this->repo->activeQuery()
            ->where('annee_id', $anneeId)
            ->orderBy('date_debut')
            ->get($this->defaultSelectFields);
    }

    /**
     * Récupère les périodes formatées pour l'affichage (avec labels de dates)
     */
    public function getAllFormatted(int $anneeId): Collection
    {
        $periodes = $this->getByAnnee($anneeId);

        return $periodes->map(function ($periode) {
            return [
                'id'         => $periode->id,
                'libelle'    => $periode->libelle,
                'date_debut' => Carbon::parse($periode->date_debut)->format('d/m/Y'),
                'date_fin'   => Carbon::parse($periode->date_fin)->format('d/m/Y'),
                'annee_id'   => $periode->annee_id,
                'etat'       => $periode->etat,
                'etat_label' => $periode->etat ? 'Actif' : 'Inactif',
            ];
        });
    }

    /**
     * Vérifie si une période a des données liées (avant suppression)
     */
    public function hasRelatedData(int $id): bool
    {
        // À adapter selon vos tables (ex: notes, évaluations, etc.)
        return \DB::table('notes')->where('periode_id', $id)->exists()
            || \DB::table('evaluations')->where('periode_id', $id)->exists();
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes avec vérification de chevauchement
    // ─────────────────────────────────────────────────────────────

    public function store(array $validatedData): object
    {
        $this->checkDateOverlap($validatedData);
        return parent::store($validatedData);
    }

    public function update(int $id, array $validatedData): object
    {
        $this->checkDateOverlap($validatedData, $id);
        return parent::update($id, $validatedData);
    }

    /**
     * Empêche le chevauchement de périodes pour une même année scolaire.
     */
    protected function checkDateOverlap(array $data, ?int $excludeId = null): void
    {
        $query = $this->repo->activeQuery()
            ->where('annee_id', $data['annee_id'])
            ->where(function ($q) use ($data) {
                $debut = $data['date_debut'];
                $fin   = $data['date_fin'];

                // Chevauchement : une période existante commence ou finit dans l'intervalle
                // ou englobe l'intervalle
                $q->whereBetween('date_debut', [$debut, $fin])
                  ->orWhereBetween('date_fin', [$debut, $fin])
                  ->orWhere(function ($sub) use ($debut, $fin) {
                      $sub->where('date_debut', '<=', $debut)
                          ->where('date_fin', '>=', $fin);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'date_debut' => 'Les dates chevauchent une période existante pour cette année.'
            ]);
        }
    }
}