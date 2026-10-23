<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Logistique\Types\TypeProduit;
use App\Domain\ServicesEleves\Types\TypeActivite;
use App\Domain\ServicesEleves\Types\TypeEvenement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Alimente les catalogues des modules à partir des frais de la base
 * historique, pour UNE année scolaire :
 *   - frais de type « Produit »   → fiches produits (boutique / stock) ;
 *   - frais de type « Activité »  → activités extrascolaires ;
 *   - frais de type « Événement » → événements.
 *
 * Le frais garde l'identifiant de la fiche créée (produit_id, activite_id,
 * evenement_id) : relancer ne crée pas de doublon. Rien n'est supprimé, les
 * paiements et les inscriptions ne sont pas touchés. Seuls les frais de ces
 * trois types sont concernés : un frais de scolarité ne génère rien.
 */
class FraisSynchronisationService
{
    public const CIBLES = ['produits' => 'Produits', 'activites' => 'Activités', 'evenements' => 'Événements'];

    /**
     * @return array{cible:string, crees:int, mis_a_jour:int, ignores:int, incoherences:array<int, string>}
     */
    public function synchroniser(string $cible, int $anneeId): array
    {
        $bilan = DB::transaction(fn () => match ($cible) {
            'produits'   => $this->produits($anneeId),
            'activites'  => $this->activites($anneeId),
            'evenements' => $this->evenements($anneeId),
            default      => throw new InvalidArgumentException("Synchronisation inconnue : {$cible}."),
        });

        return ['cible' => self::CIBLES[$cible]] + $bilan;
    }

    private function frais(int $anneeId, TypeDetail $type)
    {
        return DB::table('frais_ecoles')
            ->where('etat', 1)->where('annee_id', $anneeId)->where('type_paiement', $type->value)
            ->orderBy('id')->get();
    }

    private function bilan(): array
    {
        return ['crees' => 0, 'mis_a_jour' => 0, 'ignores' => 0, 'incoherences' => []];
    }

    private function cle(?string $libelle): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $libelle)));
    }

    /**
     * Les produits ne dépendent pas de l'année : un même article vendu chaque
     * année correspond à UNE fiche. Le prix n'est mis à jour que si tous les
     * frais de l'année qui portent ce libellé ont le même montant.
     */
    private function produits(int $anneeId): array
    {
        $bilan = $this->bilan();
        $parLibelle = $this->frais($anneeId, TypeDetail::PRODUIT)->groupBy(fn ($f) => $this->cle($f->libelle));

        foreach ($parLibelle as $cle => $frais) {
            if ($cle === '') {
                $bilan['incoherences'][] = 'Un frais de type produit n\'a pas de libellé : il est ignoré.';
                continue;
            }
            $montants = $frais->pluck('montant')->map(fn ($m) => round((float) $m, 2))->unique();
            $libelle = trim($frais->first()->libelle);

            $produitId = $frais->pluck('produit_id')->filter()->first();
            $produit = $produitId ? DB::table('produits')->where('id', $produitId)->first() : null;
            $produit ??= DB::table('produits')->whereRaw('LOWER(TRIM(libelle)) = ?', [$cle])->orderBy('id')->first();

            if ($montants->count() > 1) {
                $bilan['incoherences'][] = "« {$libelle} » a plusieurs prix cette année ("
                    . $montants->map(fn ($m) => number_format($m, 0, ',', ' '))->implode(', ') . ') : le prix de la fiche n\'est pas modifié.';
            }

            if (! $produit) {
                $id = DB::table('produits')->insertGetId([
                    'libelle'       => $libelle,
                    'prix_unitaire' => $montants->count() === 1 ? $montants->first() : null,
                    'unite_base'    => 'piece',
                    'type_produit'  => TypeProduit::VENDABLE->value,
                    'etat'          => 1,
                    'created_at'    => now(), 'updated_at' => now(),
                ]);
                $bilan['crees']++;
            } else {
                $id = (int) $produit->id;
                if ($montants->count() === 1 && abs((float) $produit->prix_unitaire - $montants->first()) > 0.005) {
                    DB::table('produits')->where('id', $id)->update(['prix_unitaire' => $montants->first(), 'updated_at' => now()]);
                    $bilan['mis_a_jour']++;
                } else {
                    $bilan['ignores']++;
                }
            }

            DB::table('frais_ecoles')->whereIn('id', $frais->pluck('id'))
                ->where(fn ($q) => $q->whereNull('produit_id')->orWhere('produit_id', '!=', $id))
                ->update(['produit_id' => $id]);
        }

        return $bilan;
    }

    /** Une activité par frais de type « Activité » de l'année (libellé, montant, niveau). */
    private function activites(int $anneeId): array
    {
        $bilan = $this->bilan();

        foreach ($this->frais($anneeId, TypeDetail::ACTIVITE) as $frais) {
            $libelle = trim((string) $frais->libelle);
            if ($libelle === '') {
                $bilan['incoherences'][] = "Le frais n° {$frais->id} n'a pas de libellé : il est ignoré.";
                continue;
            }

            $activite = $frais->activite_id ? DB::table('activites')->where('id', $frais->activite_id)->first() : null;
            $activite ??= DB::table('activites')->where('annee_id', $anneeId)
                ->whereRaw('LOWER(TRIM(libelle)) = ?', [$this->cle($libelle)])->orderBy('id')->first();

            if (! $activite) {
                $id = DB::table('activites')->insertGetId([
                    'libelle'    => $libelle,
                    'montant'    => (float) $frais->montant,
                    'type'       => TypeActivite::AUTRE->value,
                    'niveau_id'  => $frais->niveau_id,
                    'annee_id'   => $anneeId,
                    'etat'       => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $bilan['crees']++;
            } else {
                $id = (int) $activite->id;
                if ((int) $activite->annee_id !== $anneeId) {
                    $bilan['incoherences'][] = "« {$libelle} » est rattaché à une activité d'une autre année : rien n'est modifié.";
                    $bilan['ignores']++;
                    continue;
                }
                if (abs((float) $activite->montant - (float) $frais->montant) > 0.005) {
                    DB::table('activites')->where('id', $id)->update(['montant' => (float) $frais->montant, 'updated_at' => now()]);
                    $bilan['mis_a_jour']++;
                } else {
                    $bilan['ignores']++;
                }
            }

            if ((int) $frais->activite_id !== $id) {
                DB::table('frais_ecoles')->where('id', $frais->id)->update(['activite_id' => $id]);
            }
        }

        return $bilan;
    }

    /**
     * Un événement par frais de type « Événement » de l'année. Le frais ne
     * porte pas de date : celle du premier paiement est reprise, à défaut la
     * fin de l'année scolaire — dans les deux cas elle est à confirmer.
     */
    private function evenements(int $anneeId): array
    {
        $bilan = $this->bilan();
        $finAnnee = DB::table('annees')->where('id', $anneeId)->value('date_fin');

        foreach ($this->frais($anneeId, TypeDetail::EVENEMENT) as $frais) {
            $nom = trim((string) $frais->libelle);
            if ($nom === '') {
                $bilan['incoherences'][] = "Le frais n° {$frais->id} n'a pas de libellé : il est ignoré.";
                continue;
            }

            $evenement = $frais->evenement_id ? DB::table('evenements')->where('id', $frais->evenement_id)->first() : null;
            $evenement ??= DB::table('evenements')->where('annee_id', $anneeId)
                ->whereRaw('LOWER(TRIM(nom)) = ?', [$this->cle($nom)])->orderBy('id')->first();

            if (! $evenement) {
                $premierPaiement = DB::table('details')->where('frais_ecole_id', $frais->id)->where('etat', 1)
                    ->whereIn('statut_paiement', [PaiementStatut::EN_ATTENTE->value, PaiementStatut::ENCAISSE->value])
                    ->min('date_paiement');
                $date = $premierPaiement ?: $finAnnee;

                if (! $date) {
                    $bilan['incoherences'][] = "« {$nom} » : aucune date ne peut être proposée (pas de paiement, année sans date de fin) — événement non créé.";
                    continue;
                }

                $id = DB::table('evenements')->insertGetId([
                    'nom'            => $nom,
                    'type'           => TypeEvenement::AUTRE->value,
                    'date_evenement' => $date,
                    'participation'  => (float) $frais->montant,
                    'annee_id'       => $anneeId,
                    'etat'           => 1,
                    'created_at'     => now(), 'updated_at' => now(),
                ]);
                $bilan['crees']++;
                $bilan['incoherences'][] = "« {$nom} » : la date de l'événement (" . date('d/m/Y', strtotime($date)) . ') est déduite '
                    . ($premierPaiement ? 'du premier paiement' : 'de la fin de l\'année scolaire') . ' — à confirmer.';
            } else {
                $id = (int) $evenement->id;
                if ((int) $evenement->annee_id !== $anneeId) {
                    $bilan['incoherences'][] = "« {$nom} » est rattaché à un événement d'une autre année : rien n'est modifié.";
                    $bilan['ignores']++;
                    continue;
                }
                if (abs((float) $evenement->participation - (float) $frais->montant) > 0.005) {
                    DB::table('evenements')->where('id', $id)->update(['participation' => (float) $frais->montant, 'updated_at' => now()]);
                    $bilan['mis_a_jour']++;
                } else {
                    $bilan['ignores']++;
                }
            }

            if ((int) $frais->evenement_id !== $id) {
                DB::table('frais_ecoles')->where('id', $frais->id)->update(['evenement_id' => $id]);
            }
        }

        return $bilan;
    }
}
