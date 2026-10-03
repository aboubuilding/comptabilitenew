<?php

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\DTO\AffectationClasseDTO;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Inscription;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service d'affectation / désaffectation de classe.
 * Met à jour `inscriptions.classe_id` de l'inscription ACTIVE
 * (année en cours, statut validé).
 *
 * classe_id = null  →  désaffectation (retirer la classe).
 * Contrainte : tous les élèves sélectionnés doivent être du même niveau.
 */
class AffectationClasseService
{
    public function __construct(
        private readonly AnneeScolaireContext $anneeContext
    ) {}

    /**
     * @return array{affectes:int, ignores:array, desaffecte:bool}
     * @throws RuntimeException
     */
    public function affecter(AffectationClasseDTO $dto): array
    {
        $anneeId = $this->anneeContext->id();
        if (!$anneeId) {
            throw new RuntimeException("Aucune année scolaire active n'est définie.");
        }

        $classe = null;
        if ($dto->classe_id !== null) {
            $classe = Classe::actif()->find($dto->classe_id);
            if (!$classe) {
                throw new RuntimeException("Classe introuvable ou inactive.");
            }
            if ((int) $classe->annee_id !== $anneeId) {
                throw new RuntimeException("Cette classe n'appartient pas à l'année scolaire active.");
            }
        }

        $inscriptions = Inscription::with('niveau')
            ->whereIn('eleve_id', $dto->eleve_ids)
            ->where('annee_id', $anneeId)
            ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value)
            ->get();

        if ($inscriptions->isEmpty()) {
            throw new RuntimeException("Aucune inscription active trouvée pour les élèves sélectionnés.");
        }

        $niveaux = $inscriptions->pluck('niveau_id')->unique()->filter()->values();
        if ($niveaux->count() > 1) {
            throw new RuntimeException(
                "Tous les élèves sélectionnés doivent appartenir au même niveau. " .
                "Niveaux détectés : " . $niveaux->implode(', ')
            );
        }

        if ($classe && $classe->niveau_id && $niveaux->count() === 1
            && (int) $niveaux->first() !== (int) $classe->niveau_id) {
            throw new RuntimeException(
                "Le niveau de la classe sélectionnée ne correspond pas au niveau des élèves."
            );
        }

        $elevesAvecInscription = $inscriptions->pluck('eleve_id')->all();
        $ignores = array_values(array_diff($dto->eleve_ids, $elevesAvecInscription));

        $nbAffectes = DB::transaction(function () use ($inscriptions, $classe) {
            return Inscription::whereIn('id', $inscriptions->pluck('id')->all())
                ->update(['classe_id' => $classe?->id]);
        });

        return [
            'affectes'   => $nbAffectes,
            'ignores'    => $ignores,
            'desaffecte' => $classe === null,
        ];
    }
}