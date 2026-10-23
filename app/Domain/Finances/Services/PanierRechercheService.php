<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\ServicesEleves\Types\StatutParticipation;
use Illuminate\Support\Facades\DB;

class PanierRechercheService
{
    public function __construct(
        private EngagementService $engagements,
    ) {}

    /**
     * Zone 1 : engagements déjà existants pour une inscription.
     * Renvoie une ligne par nature avec le reste dû ; si l'engagement
     * n'existe pas, engagement_existe vaut false (→ renvoi vers l'écran de
     * souscription).
     *
     * @return array<int, array{
     *     nature:string, libelle:string,
     *     montant_du:float, montant_paye:float, reste_du:float,
     *     engagement_existe:bool, souscription_id:?int,
     *     lien_souscription:string
     * }>
     */
    public function engagementsDisponibles(int $inscriptionId): array
    {
        $liens = [
            NatureCreance::SCOLARITE->value => ['Écolage', url('/scolarite/inscriptions')],
            NatureCreance::CANTINE->value   => ['Cantine', url('/services-eleves/cantine/inscriptions')],
            NatureCreance::BUS->value       => ['Transport scolaire', url('/services-eleves/transport/abonnements')],
            NatureCreance::ACTIVITE->value  => ['Activités', url('/services-eleves/activites-evenements/inscriptions')],
        ];

        $result = [];

        // --- Engagements obligatoires préexistants : écolage, cantine, bus, activités ---
        foreach (EngagementService::NATURES as $nature) {
            [$libelle, $lien] = $liens[$nature->value];
            $montantDu   = $this->engagements->montantDu($inscriptionId, $nature->value);
            $montantPaye = $this->engagements->montantPaye($inscriptionId, $nature->value);

            $result[] = [
                'nature'            => $nature->value,
                'libelle'           => $libelle,
                'montant_du'        => $montantDu,
                'montant_paye'      => $montantPaye,
                'reste_du'          => max(0, round($montantDu - $montantPaye, 2)),
                'engagement_existe' => $montantDu > 0,
                'souscription_id'   => $this->souscriptionPour($inscriptionId, $nature->value),
                'lien_souscription' => $lien,
            ];
        }

        // --- Engagement conditionnel : événements à capacité limitée, en pré-inscription ---
        $preInscriptions = DB::table('participation_evenements as pe')
            ->join('evenements as e', 'e.id', '=', 'pe.evenement_scolaire_id')
            ->where('pe.inscription_id', $inscriptionId)
            ->where('pe.etat', 1)
            ->where('pe.statut', StatutParticipation::PRE_INSCRIT->value)
            ->get(['e.id as evenement_id', 'e.participation', 'e.nom']);

        foreach ($preInscriptions as $pre) {
            $montantDu   = round((float) $pre->participation, 2);
            $montantPaye = round((float) DB::table('details')
                ->where('inscription_id', $inscriptionId)
                ->where('evenement_id', $pre->evenement_id)
                ->where('etat', 1)
                ->where('statut_paiement', PaiementStatut::ENCAISSE->value)
                ->sum('montant'), 2);

            $result[] = [
                'nature'            => NatureCreance::AUTRE->value,
                'libelle'           => 'Pré-inscription : ' . $pre->nom,
                'montant_du'        => $montantDu,
                'montant_paye'      => $montantPaye,
                'reste_du'          => max(0, round($montantDu - $montantPaye, 2)),
                'engagement_existe' => true,
                'souscription_id'   => null,
                'evenement_id'      => $pre->evenement_id,
                'lien_souscription' => url('/services-eleves/activites-evenements/evenements'),
            ];
        }

        return $result;
    }

    /**
     * Zone 2 : recherche libre — produits en stock, services, événements
     * sans capacité limitée.
     *
     * @return array{lignes: array<int, array<string, mixed>>}
     */
    public function rechercher(string $query, ?string $type = null, int $limit = 20, ?int $inscriptionId = null): array
    {
        $lignes = [];

        // Articles et prestations vendus comme « frais » (T-shirt, livres, kits,
        // photo…) : c'est le catalogue de la base historique. Les frais qui
        // créent un engagement (scolarité, cantine, bus, activités…) n'y
        // figurent pas : ils passent par une souscription.
        if (! $type || $type === 'frais') {
            $niveauId = $inscriptionId
                ? DB::table('inscriptions')->where('id', $inscriptionId)->value('niveau_id')
                : null;

            $typesLibres = array_map(
                fn (TypeDetail $t) => $t->value,
                array_filter(TypeDetail::cases(), fn (TypeDetail $t) => $t->natureRecouvrement() === null && $t !== TypeDetail::EVENEMENT)
            );

            $frais = DB::table('frais_ecoles')
                ->where('etat', 1)
                ->where('annee_id', app(\App\Support\AnneeScolaireContext::class)->id())
                ->whereIn('type_paiement', $typesLibres)
                ->when($query !== '', fn ($q) => $q->where('libelle', 'like', "%{$query}%"))
                ->where(function ($q) use ($niveauId) {
                    $q->whereNull('niveau_id');
                    if ($niveauId) {
                        $q->orWhere('niveau_id', $niveauId);
                    }
                })
                ->orderBy('libelle')
                ->limit($query === '' ? 50 : $limit)
                ->get(['id', 'libelle', 'montant', 'type_paiement']);

            foreach ($frais as $f) {
                $lignes[] = [
                    'type'          => 'frais',
                    'type_paiement' => (int) $f->type_paiement,
                    'id'            => $f->id,
                    'libelle'       => $f->libelle,
                    'prix_unitaire' => (float) $f->montant,
                    'requires_qte'  => true,
                ];
            }
        }

        // Sans mot-clé ni type demandé, on ne propose que le catalogue des frais.
        if ($query === '' && ! $type) {
            return ['lignes' => $lignes];
        }

        if (! $type || $type === 'produit') {
            // Produits avec stock > 0 (stock_actuel : quantité courante par magasin)
            $produits = DB::table('produits as p')
                ->leftJoin('stock_actuel as s', 's.produit_id', '=', 'p.id')
                ->where('p.etat', 1)
                ->where(function ($q) use ($query) {
                    $q->where('p.libelle', 'like', "%{$query}%")
                      ->orWhere('p.code', 'like', "%{$query}%");
                })
                ->selectRaw('p.id, p.libelle, p.prix_unitaire as prix, COALESCE(SUM(s.quantite), 0) as stock')
                ->groupBy('p.id', 'p.libelle', 'p.prix_unitaire')
                ->havingRaw('COALESCE(SUM(s.quantite), 0) > 0')
                ->limit($limit)
                ->get();

            foreach ($produits as $p) {
                $lignes[] = [
                    'type'          => 'produit',
                    'type_paiement' => TypeDetail::PRODUIT->value,
                    'id'            => $p->id,
                    'libelle'       => $p->libelle,
                    'prix_unitaire' => (float) $p->prix,
                    'stock'         => (float) $p->stock,
                    'requires_qte'  => true,
                ];
            }
        }

        if (! $type || $type === 'service') {
            $services = DB::table('services')
                ->where('etat', 1)
                ->where('libelle', 'like', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'libelle', 'prix_unitaire']);

            foreach ($services as $s) {
                $lignes[] = [
                    'type'          => 'service',
                    'type_paiement' => TypeDetail::SERVICE->value,
                    'id'            => $s->id,
                    'libelle'       => $s->libelle,
                    'prix_unitaire' => (float) $s->prix_unitaire,
                    'requires_qte'  => false,
                ];
            }
        }

        // Année clôturée : plus de participation à un événement (comme pour toute souscription)
        $anneeEleve = $inscriptionId ? DB::table('inscriptions')->where('id', $inscriptionId)->value('annee_id') : null;
        $anneeCloturee = $anneeEleve && \App\Domain\Scolarite\Models\Annee::find($anneeEleve)?->isCloturee();

        if ((! $type || $type === 'evenement') && ! $anneeCloturee) {
            // Uniquement les événements SANS capacité limitée
            $evenements = DB::table('evenements')
                ->where('etat', 1)
                ->where(function ($q) {
                    $q->where('capacite_limitee', 0)->orWhereNull('capacite_limitee');
                })
                ->where('nom', 'like', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'nom', 'participation']);

            foreach ($evenements as $e) {
                $lignes[] = [
                    'type'          => 'evenement',
                    'type_paiement' => TypeDetail::EVENEMENT->value,
                    'id'            => $e->id,
                    'libelle'       => $e->nom,
                    'prix_unitaire' => (float) ($e->participation ?? 0),
                    'requires_qte'  => false,
                ];
            }
        }

        return ['lignes' => $lignes];
    }

    /* =================================================================
       Helpers privés
       ================================================================= */

    /** Souscription à rattacher aux lignes de paiement de cette nature, s'il y en a une. */
    private function souscriptionPour(int $inscriptionId, string $nature): ?int
    {
        $codes = $this->engagements->codesPour($nature);
        if ($codes === []) {
            return null;
        }

        return DB::table('souscriptions')
            ->where('inscription_id', $inscriptionId)
            ->where('etat', 1)
            ->whereIn('type_paiement', $codes)
            ->orderBy('id')
            ->value('id');
    }
}
