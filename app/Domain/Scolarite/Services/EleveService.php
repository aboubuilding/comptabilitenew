<?php

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\DTO\EleveDTO;
use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Repositories\EleveRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Logique métier des élèves.
 */
class EleveService
{
    public function __construct(
        private readonly EleveRepository $repository,
        private readonly AnneeScolaireContext $anneeContext
    ) {}

    public function creer(EleveDTO $dto, ?UploadedFile $photo = null, ?UploadedFile $carteIdentite = null): Eleve
    {
        return DB::transaction(function () use ($dto, $photo, $carteIdentite) {
            $data = $dto->toArray();
            $data['matricule'] = $this->repository->genererMatricule();

            if ($photo) {
                $data['photo'] = $photo->store('eleves/photos', 'public');
            }
            if ($carteIdentite) {
                $data['carte_identite'] = $carteIdentite->store('eleves/cartes', 'public');
            }

            return $this->repository->create($data);
        });
    }

    public function mettreAJour(
        Eleve $eleve,
        EleveDTO $dto,
        ?UploadedFile $photo = null,
        ?UploadedFile $carteIdentite = null
    ): Eleve {
        return DB::transaction(function () use ($eleve, $dto, $photo, $carteIdentite) {
            $data = $dto->toArray();

            if ($photo) {
                if ($eleve->photo) {
                    Storage::disk('public')->delete($eleve->photo);
                }
                $data['photo'] = $photo->store('eleves/photos', 'public');
            }
            if ($carteIdentite) {
                if ($eleve->carte_identite) {
                    Storage::disk('public')->delete($eleve->carte_identite);
                }
                $data['carte_identite'] = $carteIdentite->store('eleves/cartes', 'public');
            }

            $this->repository->update($eleve, $data);
            return $eleve->fresh(['nationalite', 'inscriptionActive.classe', 'inscriptionActive.niveau', 'inscriptionActive.cycle']);
        });
    }

    public function archiver(Eleve $eleve): void
    {
        $this->repository->delete($eleve);
    }

    /**
     * Statistiques sur l'année active.
     */
    public function statistiques(): array
    {
        $anneeId = $this->anneeContext->id();

        if (!$anneeId) {
            return [
                'annee_id'  => null,
                'total'     => 0,
                'masculins' => 0,
                'feminins'  => 0,
            ];
        }

        $base = Eleve::query()
            ->where('etat', 1)
            ->whereHas('inscriptions', function ($q) use ($anneeId) {
                $q->where('annee_id', $anneeId)
                  ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value);
            });

        return [
            'annee_id'  => $anneeId,
            'total'     => (clone $base)->count(),
            'masculins' => (clone $base)->where('sexe', 1)->count(),
            'feminins'  => (clone $base)->where('sexe', 2)->count(),
        ];
    }
}