<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\Detail;
use App\Domain\Finances\Models\Paiement;
use App\Domain\Finances\Repositories\PaiementRepository;
use App\Domain\Finances\Types\StatutPaiement;
use App\Domain\ServicesEleves\Models\AbonnementBus;
use App\Domain\ServicesEleves\Models\InscriptionActivite;
use App\Domain\ServicesEleves\Models\InscriptionCantine;
use App\Domain\ServicesEleves\Models\ParticipationEvenement;
use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Porte la logique la plus complexe du module Finances : agrégation des
 * engagements existants d'un élève (zone 1 de l'écran Paiements), et
 * création d'un paiement unique ventilé en plusieurs lignes Detail
 * (zone 3) à partir des engagements sélectionnés + du panier.
 *
 * SIMPLIFICATIONS ASSUMÉES, à affiner avec vous :
 * - "Montant déjà payé" par nature ne compte que les lignes déjà
 *   ENCAISSÉES (date_encaissement renseignée) — un paiement saisi mais
 *   pas encore encaissé ne réduit donc pas encore le reste dû affiché.
 *   À revoir si le métier attend l'inverse.
 * - Pour Activités/Événements, "payé par CET élève" passe par
 *   paiement -> inscription -> eleve_id (Detail n'a pas de eleve_id
 *   direct) : correct tant qu'un Paiement reste propre à un seul élève,
 *   ce qui est le cas ici.
 */
class PaiementService
{
    public function __construct(private PaiementRepository $paiements)
    {
    }

    public function engagementsPourEleve(Eleve $eleve, int $anneeId): array
    {
        $engagements = [];

        $inscription = Inscription::where('eleve_id', $eleve->id)
            ->where('annee_id', $anneeId)
            ->where('statut_validation', StatutValidationInscription::Validee->value)
            ->where('statut_abandon', 0)
            ->first();

        if (! $inscription) {
            return $engagements; // pas d'inscription validée -> aucun engagement possible
        }

        $payeEcolage = Detail::where('inscription_id', $inscription->id)
            ->whereNull('produit_id')->whereNull('service_id')->whereNull('activite_id')
            ->whereNull('evenement_id')->whereNull('abonnement_bus_id')->whereNull('inscription_cantine_id')
            ->whereNotNull('date_encaissement')
            ->sum('montant');

        $engagements[] = [
            'nature'            => 'ecolage',
            'label'             => 'Écolage',
            'reference_id'      => $inscription->id,
            'montant_engage'    => $inscription->montant_engage,
            'montant_paye'      => (float) $payeEcolage,
            'montant_du'        => max(0, $inscription->montant_engage - $payeEcolage),
            'lien_souscription' => null,
        ];

        $cantine = InscriptionCantine::where('inscription_id', $inscription->id)->where('statut', 1)->first();
        if ($cantine) {
            $paye = Detail::where('inscription_cantine_id', $cantine->id)->whereNotNull('date_encaissement')->sum('montant');
            $engagements[] = [
                'nature' => 'cantine', 'label' => 'Cantine', 'reference_id' => $cantine->id,
                'montant_engage' => (float) $cantine->montant_total_du, 'montant_paye' => (float) $paye,
                'montant_du' => max(0, $cantine->montant_total_du - $paye), 'lien_souscription' => null,
            ];
        } else {
            $engagements[] = [
                'nature' => 'cantine', 'label' => 'Cantine', 'reference_id' => null,
                'montant_engage' => null, 'montant_paye' => null, 'montant_du' => null,
                'lien_souscription' => '#', // TODO : route de souscription cantine, module Services aux Élèves
            ];
        }

        $bus = AbonnementBus::where('inscription_id', $inscription->id)->where('statut', 1)->first();
        if ($bus) {
            $paye = Detail::where('abonnement_bus_id', $bus->id)->whereNotNull('date_encaissement')->sum('montant');
            $engagements[] = [
                'nature' => 'bus', 'label' => 'Bus', 'reference_id' => $bus->id,
                'montant_engage' => (float) $bus->montant_total_du, 'montant_paye' => (float) $paye,
                'montant_du' => max(0, $bus->montant_total_du - $paye), 'lien_souscription' => null,
            ];
        } else {
            $engagements[] = [
                'nature' => 'bus', 'label' => 'Bus', 'reference_id' => null,
                'montant_engage' => null, 'montant_paye' => null, 'montant_du' => null,
                'lien_souscription' => '#', // TODO : route de souscription bus, module Services aux Élèves
            ];
        }

        foreach (InscriptionActivite::where('eleve_id', $eleve->id)->where('annee_id', $anneeId)->where('etat', 1)->with('activite')->get() as $ia) {
            $paye = Detail::where('activite_id', $ia->activite_id)
                ->whereHas('paiement.inscription', fn ($q) => $q->where('eleve_id', $eleve->id))
                ->whereNotNull('date_encaissement')
                ->sum('montant');

            $engagements[] = [
                'nature' => 'activite', 'label' => 'Activité — ' . ($ia->activite->libelle ?? '?'),
                'reference_id' => $ia->id, 'montant_engage' => (float) $ia->montant_du, 'montant_paye' => (float) $paye,
                'montant_du' => max(0, $ia->montant_du - $paye), 'lien_souscription' => null,
            ];
        }

        foreach (ParticipationEvenement::where('inscripion_id', $inscription->id)->where('etat', 1)->with('evenement')->get() as $pe) {
            $paye = Detail::where('evenement_id', $pe->evenement_scolaire_id)
                ->whereHas('paiement.inscription', fn ($q) => $q->where('eleve_id', $eleve->id))
                ->whereNotNull('date_encaissement')
                ->sum('montant');

            $engagements[] = [
                'nature' => 'evenement', 'label' => 'Événement — ' . ($pe->evenement->nom ?? '?'),
                'reference_id' => $pe->id, 'montant_engage' => (float) $pe->montant_facture, 'montant_paye' => (float) $paye,
                'montant_du' => max(0, $pe->montant_facture - $paye), 'lien_souscription' => null,
            ];
        }

        return $engagements;
    }

    /**
     * @param array $data ['eleve_id','inscription_id','payeur','telephone_payeur','mode_paiement',
     *                     'annee_id','utilisateur_id',
     *                     'engagements' => [['nature','reference_id','montant']],
     *                     'panier'      => [['nature','id','quantite'?,'montant']]]
     */
    public function enregistrerPaiement(array $data): Paiement
    {
        return DB::transaction(function () use ($data) {
            $montantTotal = collect($data['engagements'] ?? [])->sum('montant')
                + collect($data['panier'] ?? [])->sum('montant');

            $paiement = $this->paiements->create([
                'reference'        => 'PAY-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5)),
                'payeur'           => $data['payeur'],
                'telephone_payeur' => $data['telephone_payeur'] ?? null,
                'date_paiement'    => now(),
                'statut_paiement'  => StatutPaiement::EnAttente->value,
                'mode_paiement'    => $data['mode_paiement'],
                'inscription_id'   => $data['inscription_id'],
                'utilisateur_id'   => $data['utilisateur_id'],
                'annee_id'         => $data['annee_id'],
                'montant'          => $montantTotal,
            ]);

            foreach ($data['engagements'] ?? [] as $engagement) {
                $this->creerLigneEngagement($paiement, $data, $engagement);
            }

            foreach ($data['panier'] ?? [] as $item) {
                $this->creerLignePanier($paiement, $data, $item);
            }

            return $paiement;
        });
    }

    private function creerLigneEngagement(Paiement $paiement, array $data, array $engagement): void
    {
        $base = [
            'paiement_id'     => $paiement->id,
            'montant'         => $engagement['montant'],
            'annee_id'        => $data['annee_id'],
            'statut_paiement' => StatutPaiement::EnAttente->value,
            'date_paiement'   => now(),
            'comptable_id'    => $data['utilisateur_id'],
        ];

        switch ($engagement['nature']) {
            case 'ecolage':
                Detail::create([...$base, 'libelle' => 'Écolage', 'inscription_id' => $engagement['reference_id']]);
                break;
            case 'cantine':
                Detail::create([...$base, 'libelle' => 'Cantine', 'inscription_cantine_id' => $engagement['reference_id']]);
                break;
            case 'bus':
                Detail::create([...$base, 'libelle' => 'Bus', 'abonnement_bus_id' => $engagement['reference_id']]);
                break;
            case 'activite':
                $ia = InscriptionActivite::find($engagement['reference_id']);
                Detail::create([...$base, 'libelle' => 'Activité — ' . ($ia?->activite?->libelle ?? ''), 'activite_id' => $ia?->activite_id]);
                break;
            case 'evenement':
                $pe = ParticipationEvenement::find($engagement['reference_id']);
                Detail::create([...$base, 'libelle' => 'Événement — ' . ($pe?->evenement?->nom ?? ''), 'evenement_id' => $pe?->evenement_scolaire_id]);
                break;
        }
    }

    private function creerLignePanier(Paiement $paiement, array $data, array $item): void
    {
        $base = [
            'paiement_id'     => $paiement->id,
            'montant'         => $item['montant'],
            'annee_id'        => $data['annee_id'],
            'statut_paiement' => StatutPaiement::EnAttente->value,
            'date_paiement'   => now(),
            'comptable_id'    => $data['utilisateur_id'],
        ];

        switch ($item['nature']) {
            case 'produit':
                $produit = \App\Domain\Logistique\Models\Produit::find($item['id']);
                Detail::create([...$base, 'libelle' => $produit?->libelle, 'produit_id' => $item['id'], 'quantite' => $item['quantite'] ?? 1]);
                break;
            case 'service':
                $service = \App\Domain\Finances\Models\Service::find($item['id']);
                Detail::create([...$base, 'libelle' => $service?->libelle, 'service_id' => $item['id']]);
                break;
            case 'evenement':
                $evenement = \App\Domain\ServicesEleves\Models\Evenement::find($item['id']);
                Detail::create([...$base, 'libelle' => $evenement?->nom, 'evenement_id' => $item['id']]);
                break;
        }
    }
}