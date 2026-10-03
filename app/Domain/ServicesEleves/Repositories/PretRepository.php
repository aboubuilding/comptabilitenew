<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Pret;
use App\Domain\ServicesEleves\Types\StatutPret;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PretRepository extends BaseRepository
{
    public function __construct(Pret $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'livre:id,titre,auteur_id',
                'livre.auteur:id,nom_prenom',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'utilisateur:id,name',
            ])
            ->when($filters['statut_pret'] ?? null, fn ($q, $v) => $q->where('statut_pret', $v))
            ->when($filters['livre_id'] ?? null, fn ($q, $v) => $q->where('livre_id', $v))
            ->when($filters['en_retard'] ?? null, function ($q) {
                $q->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value])
                  ->whereDate('date_retour_pret', '<', now());
            })
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->whereHas('livre', fn ($l) => $l->where('titre', 'like', "%{$v}%"))
                        ->orWhereHas('inscription.eleve', function ($e) use ($v) {
                            $e->where('nom', 'like', "%{$v}%")
                              ->orWhere('prenom', 'like', "%{$v}%")
                              ->orWhere('matricule', 'like', "%{$v}%");
                        });
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_pret', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_pret', '<=', $v))
            ->orderByDesc('date_pret')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Prêts en retard (retour prévu dépassé, non rendu). */
    public function enRetard(): Collection
    {
        return $this->activeQuery()
            ->with([
                'livre:id,titre',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
            ])
            ->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value])
            ->whereDate('date_retour_pret', '<', now())
            ->orderBy('date_retour_pret')
            ->get();
    }

    public function compterStatistiques(): array
    {
        $enCours = $this->activeQuery()
            ->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value]);

        return [
            'total'    => $this->activeQuery()->count(),
            'en_cours' => (clone $enCours)->count(),
            'en_retard'=> (clone $enCours)->whereDate('date_retour_pret', '<', now())->count(),
            'rendus'   => $this->activeQuery()->where('statut_pret', StatutPret::RENDU->value)->count(),
        ];
    }
}