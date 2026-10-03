<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\ServicesEleves\DTO\LivreData;
use App\Domain\ServicesEleves\DTO\PretData;
use App\Domain\ServicesEleves\Models\Livre;
use App\Domain\ServicesEleves\Models\Pret;
use App\Domain\ServicesEleves\Repositories\LivreRepository;
use App\Domain\ServicesEleves\Repositories\PretRepository;
use App\Domain\ServicesEleves\Types\StatutPret;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BibliothequeService
{
    public function __construct(
        private LivreRepository $livres,
        private PretRepository  $prets,
    ) {}

    /* ================================================================
       CATALOGUE
       ================================================================ */

    public function creerLivre(LivreData $data): Livre
    {
        if ($data->nombreExemplaires < 1) {
            throw new RuntimeException("Le nombre d'exemplaires doit être au moins 1.");
        }

        return DB::transaction(fn () => $this->livres->create([
            'titre'              => $data->titre,
            'numero'             => $data->numero,
            'numero_isbn'        => $data->numeroIsbn,
            'auteur_id'          => $data->auteurId,
            'maison_edition_id'  => $data->maisonEditionId,
            'annee_edition_id'   => $data->anneeEditionId,
            'categorie_livre_id' => $data->categorieLivreId,
            'nombre_exemplaires' => $data->nombreExemplaires,
            'nombre_disponibles' => $data->nombreExemplaires,
            'description'        => $data->description,
        ]));
    }

    public function modifierLivre(int $id, LivreData $data): Livre
    {
        $livre = $this->livres->findOrFail($id);

        // Ajuster le nombre_disponibles si le total change
        $enPret = $livre->nombre_exemplaires - $livre->nombre_disponibles;
        $nouveauxDispos = max(0, $data->nombreExemplaires - $enPret);

        if ($data->nombreExemplaires < $enPret) {
            throw new RuntimeException(
                "Impossible : {$enPret} exemplaire(s) sont actuellement en prêt."
            );
        }

        $this->livres->update($id, [
            'titre'              => $data->titre,
            'numero'             => $data->numero,
            'numero_isbn'        => $data->numeroIsbn,
            'auteur_id'          => $data->auteurId,
            'maison_edition_id'  => $data->maisonEditionId,
            'annee_edition_id'   => $data->anneeEditionId,
            'categorie_livre_id' => $data->categorieLivreId,
            'nombre_exemplaires' => $data->nombreExemplaires,
            'nombre_disponibles' => $nouveauxDispos,
            'description'        => $data->description,
        ]);

        return $livre->refresh();
    }

    public function supprimerLivre(int $id): void
    {
        $livre = $this->livres->findOrFail($id);

        if ($livre->prets()->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value])->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer : ce livre a des prêts en cours."
            );
        }

        $this->livres->delete($id);
    }

    /* ================================================================
       PRÊTS
       ================================================================ */

    /**
     * Enregistre un prêt.
     * Règle : un élève ne peut pas avoir 2 fois le même livre en cours.
     * Décrémente le compteur d'exemplaires disponibles.
     */
    public function enregistrerPret(PretData $data, int $userId): Pret
    {
        return DB::transaction(function () use ($data, $userId) {
            $livre = $this->livres->findOrFail($data->livreId);

            // 1) Vérifie la disponibilité
            if ($livre->nombre_disponibles < 1) {
                throw new RuntimeException(
                    "Aucun exemplaire disponible pour « {$livre->titre} »."
                );
            }

            // 2) Vérifie que l'élève n'a pas déjà ce livre
            $dejaEnPret = $this->prets->activeQuery()
                ->where('livre_id', $data->livreId)
                ->where('inscription_id', $data->inscriptionId)
                ->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value])
                ->exists();

            if ($dejaEnPret) {
                throw new RuntimeException(
                    "Cet élève a déjà ce livre en prêt."
                );
            }

            // 3) Crée le prêt
            $anneeId = app(AnneeScolaireContext::class)->id();

            $pret = $this->prets->create([
                'livre_id'         => $livre->id,
                'inscription_id'   => $data->inscriptionId,
                'date_pret'        => $data->datePret,
                'date_retour_pret' => $data->dateRetourPret,
                'statut_pret'      => StatutPret::EN_COURS->value,
                'caution'          => $data->caution,
                'observations'     => $data->observations,
                'utilisateur_id'   => $userId,
                'annee_id'         => $anneeId,
            ]);

            // 4) Décrémente le disponible
            $this->livres->update($livre->id, [
                'nombre_disponibles' => $livre->nombre_disponibles - 1,
            ]);

            return $pret->fresh('livre');
        });
    }

    /**
     * Enregistre un retour.
     * Incrémente le compteur d'exemplaires disponibles.
     */
    public function enregistrerRetour(int $pretId, ?string $dateRetourReel = null, ?string $observations = null): Pret
    {
        return DB::transaction(function () use ($pretId, $dateRetourReel, $observations) {
            $pret = $this->prets->findOrFail($pretId);

            if (! $pret->isEnCours()) {
                throw new RuntimeException("Ce prêt est déjà clôturé.");
            }

            $this->prets->update($pret->id, [
                'date_retour_reel' => $dateRetourReel ?? now()->toDateString(),
                'statut_pret'      => StatutPret::RENDU->value,
                'observations'     => $observations ?? $pret->observations,
            ]);

            // Ré-incrémente le disponible
            $livre = $this->livres->findOrFail($pret->livre_id);
            $this->livres->update($livre->id, [
                'nombre_disponibles' => min(
                    $livre->nombre_exemplaires,
                    $livre->nombre_disponibles + 1
                ),
            ]);

            return $pret->refresh();
        });
    }

    /** Marque un prêt comme perdu (n'incrémente PAS le disponible). */
    public function marquerPerdu(int $pretId, string $observations): Pret
    {
        return DB::transaction(function () use ($pretId, $observations) {
            $pret = $this->prets->findOrFail($pretId);

            if (! $pret->isEnCours()) {
                throw new RuntimeException("Ce prêt est déjà clôturé.");
            }

            $this->prets->update($pret->id, [
                'statut_pret'  => StatutPret::PERDU->value,
                'observations' => $observations,
            ]);

            // On retire définitivement l'exemplaire du stock
            $livre = $this->livres->findOrFail($pret->livre_id);
            $this->livres->update($livre->id, [
                'nombre_exemplaires' => max(0, $livre->nombre_exemplaires - 1),
            ]);

            return $pret->refresh();
        });
    }

    /**
     * Recalcule les statuts "RETARD" pour tous les prêts en cours dont la
     * date de retour est dépassée. Appelé par un job quotidien ou à la
     * volée depuis la page prêts.
     */
    public function recalculerRetards(): int
    {
        return $this->prets->activeQuery()
            ->where('statut_pret', StatutPret::EN_COURS->value)
            ->whereDate('date_retour_pret', '<', now())
            ->update([
                'statut_pret'          => StatutPret::RETARD->value,
                'date_retard_constate' => now()->toDateString(),
            ]);
    }
}