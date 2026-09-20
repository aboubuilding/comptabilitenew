<?php

namespace App\Support;

/**
 * Source de vérité unique pour "l'année scolaire active" de la requête en
 * cours. Renseigné par le middleware ResolveAnneeScolaire en tout début
 * de requête ; lu ensuite par BaseRepository (injection auto d'annee_id),
 * HeaderComposer (affichage), et tout Repository/Service qui en a besoin.
 *
 * Ne résout jamais rien lui-même (pas d'accès à la session ou à la base
 * ici) : c'est une simple boîte à état pour la durée de la requête.
 */
class AnneeScolaireContext
{
    protected ?int $anneeId = null;

    public function set(int $anneeId): void
    {
        $this->anneeId = $anneeId;
    }

    public function id(): ?int
    {
        return $this->anneeId;
    }
}