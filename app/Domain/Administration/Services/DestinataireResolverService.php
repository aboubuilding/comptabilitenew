<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\DTO\DestinataireDTO;
use App\Domain\Administration\Models\ContactTiers;
use Illuminate\Support\Facades\DB;

/**
 * Résout un ciblage (parents, classe, niveau, tiers, libre) en une liste
 * de DestinataireDTO prêts à être insérés.
 *
 * ⚠️ Adapte les requêtes SQL aux tables réelles de votre projet
 * (parent_eleves, espaces, inscriptions, classes, niveaux, eleves).
 */
class DestinataireResolverService
{
    /**
     * @param  array  $ciblage  Ex:
     *   [
     *     'type'         => 'parents'|'classe'|'niveau'|'tiers'|'libre',
     *     'classe_id'    => 12,
     *     'niveau_id'    => 3,
     *     'tiers_ids'    => [1,2,3],
     *     'emails_libres'=> ['a@b.c'],
     *     'annee_id'     => 1,
     *   ]
     * @return DestinataireDTO[]
     */
    public function resoudre(array $ciblage): array
    {
        return match ($ciblage['type'] ?? 'libre') {
            'parents' => $this->resoudreParents($ciblage),
            'classe'  => $this->resoudreParClasse($ciblage),
            'niveau'  => $this->resoudreParNiveau($ciblage),
            'tiers'   => $this->resoudreTiers($ciblage),
            'libre'   => $this->resoudreLibre($ciblage),
            default   => [],
        };
    }

    /**
     * Parents désignés individuellement (identifiants de parent_eleves).
     */
    protected function resoudreParents(array $ciblage): array
    {
        $ids = $ciblage['parent_ids'] ?? [];
        if (empty($ids)) {
            return [];
        }

        return $this->versDestinataires(
            $this->parentsJoignables()->whereIn('pe.id', $ids)
        );
    }

    /**
     * Parents des élèves d'une classe : l'élève est rattaché à un espace
     * familial (eleves.espace_id), qui regroupe ses parents (parent_eleves.espace_id).
     */
    protected function resoudreParClasse(array $ciblage): array
    {
        $classeId = $ciblage['classe_id'] ?? null;
        if (!$classeId) {
            return [];
        }

        return $this->versDestinataires(
            $this->parentsDesInscrits($ciblage['annee_id'] ?? null)->where('i.classe_id', $classeId)
        );
    }

    /**
     * Parents des élèves d'un niveau.
     */
    protected function resoudreParNiveau(array $ciblage): array
    {
        $niveauId = $ciblage['niveau_id'] ?? null;
        if (!$niveauId) {
            return [];
        }

        return $this->versDestinataires(
            $this->parentsDesInscrits($ciblage['annee_id'] ?? null)->where('i.niveau_id', $niveauId)
        );
    }

    /** Parents actifs disposant d'une adresse email. */
    private function parentsJoignables()
    {
        return DB::table('parent_eleves as pe')
            ->where('pe.etat', 1)
            ->whereNotNull('pe.email')
            ->where('pe.email', '!=', '');
    }

    private function parentsDesInscrits(?int $anneeId)
    {
        return $this->parentsJoignables()
            ->join('eleves as el', 'el.espace_id', '=', 'pe.espace_id')
            ->join('inscriptions as i', 'i.eleve_id', '=', 'el.id')
            ->where('i.etat', 1)
            ->when($anneeId, fn ($q) => $q->where('i.annee_id', $anneeId));
    }

    /** Un parent de plusieurs élèves de la même classe ne reçoit le message qu'une fois. */
    private function versDestinataires($query): array
    {
        return $query
            ->distinct()
            ->get(['pe.id', 'pe.email', 'pe.nom_parent', 'pe.prenom_parent'])
            ->unique('email')
            ->map(fn ($r) => new DestinataireDTO(
                email: $r->email,
                nom:   trim(($r->nom_parent ?? '') . ' ' . ($r->prenom_parent ?? '')) ?: null,
                type:  'parent',
                id:    (int) $r->id,
            ))
            ->values()
            ->all();
    }

    /**
     * Contacts tiers sélectionnés.
     */
    protected function resoudreTiers(array $ciblage): array
    {
        $ids = $ciblage['tiers_ids'] ?? [];
        if (empty($ids)) {
            return [];
        }

        $contacts = ContactTiers::actif()->whereIn('id', $ids)->get();

        return $contacts->map(fn ($c) => new DestinataireDTO(
            email: $c->email,
            nom:   $c->nom,
            type:  'tiers',
            id:    $c->id,
        ))->all();
    }

    /**
     * Adresses libres saisies à la main.
     */
    protected function resoudreLibre(array $ciblage): array
    {
        $emails = $ciblage['emails_libres'] ?? [];
        $result = [];

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result[] = new DestinataireDTO(
                    email: $email,
                    nom:   null,
                    type:  'libre',
                    id:    null,
                );
            }
        }

        return $result;
    }
}