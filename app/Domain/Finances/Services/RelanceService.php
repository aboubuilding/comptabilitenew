<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\DossierRecouvrement;
use App\Domain\Finances\Models\Relance;
use App\Domain\Finances\Repositories\DossierRecouvrementRepository;
use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\ResultatRelance;
use App\Domain\Finances\Types\TypeRelance;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RelanceService
{
    public function __construct(
        private DossierRecouvrementRepository $dossiers,
    ) {}

    /**
     * Enregistre une action de relance.
     * Règles :
     *  - dossier non clos
     *  - date_relance <= aujourd'hui
     *  - si resultat = paiement_total, clôture automatique du dossier
     */
    public function enregistrer(
        int $dossierId,
        string $typeRelance,
        string $dateRelance,
        ?string $contenu,
        ?string $resultat,
        ?string $dateProchaine,
        ?int $userId,
        ?float $montantDuMoment = null,
        ?int $messageId = null,
    ): Relance {
        return DB::transaction(function () use (
            $dossierId, $typeRelance, $dateRelance, $contenu, $resultat,
            $dateProchaine, $userId, $montantDuMoment, $messageId
        ) {
            $dossier = $this->dossiers->findOrFail($dossierId);

            if ($dossier->isClos()) {
                throw new RuntimeException(
                    "Ce dossier est déjà clos ({$dossier->statut->label()}) — aucune relance possible."
                );
            }

            $relance = Relance::create([
                'dossier_recouvrement_id' => $dossier->id,
                'type_relance'            => $typeRelance,
                'date_relance'            => $dateRelance,
                'contenu'                 => $contenu,
                'montant_du_moment'       => $montantDuMoment ?? $dossier->montant_restant,
                'resultat'                => $resultat,
                'date_prochaine_relance'  => $dateProchaine,
                'utilisateur_id'          => $userId,
                'message_id'              => $messageId,
                'annee_id'                => $dossier->annee_id,
            ]);

            // Cas particuliers : ajustement du dossier selon le résultat.
            if ($resultat === ResultatRelance::PAIEMENT_TOTAL->value) {
                $this->dossiers->update($dossier->id, [
                    'montant_recouvre' => $dossier->montant_initial_du,
                    'statut'           => DossierStatut::SOLDE->value,
                    'date_cloture'     => now()->toDateString(),
                    'motif_cloture'    => 'Paiement total constaté lors de la relance',
                ]);
            } elseif ($resultat === ResultatRelance::PROMESSE_PAIEMENT->value) {
                $this->dossiers->update($dossier->id, [
                    'statut' => DossierStatut::ECHEANCIER->value,
                ]);
            }

            return $relance;
        });
    }

    /** Modèles de message proposés (variables : {eleve} {matricule} {classe} {montant} {echeances} {date}). */
    public const MODELES = [
        'email' => "Bonjour,\n\nSauf erreur de notre part, {eleve} ({classe}) reste redevable de {montant} F au titre de : {echeances}.\n"
            . "Nous vous remercions de bien vouloir régulariser cette situation au plus tôt.\n\nL'École Internationale Mariam",
        'sms'   => "EIM : {eleve} reste redevable de {montant} F ({echeances}). Merci de régulariser au plus tôt.",
    ];

    /**
     * Relances par message (email ou SMS), à un élève ou à un groupe : une
     * relance par élève, adressée au parent principal (son email ou son
     * téléphone), rattachée au dossier ouvert de l'élève s'il en a un. Les
     * messages sont enregistrés « à envoyer » ; un parent sans adresse pour le
     * canal choisi est signalé « sans destinataire ».
     *
     * @param  array<int, array{montant:float, echeances:string}>  $cibles  inscription_id => ce qui est réclamé
     * @return array{relances:int, sans_destinataire:int, lot:string}
     */
    public function relancerParMessage(array $cibles, string $canal, string $modele, ?int $userId): array
    {
        if (! in_array($canal, [TypeRelance::EMAIL->value, TypeRelance::SMS->value], true)) {
            throw new RuntimeException("Une relance s'envoie par email ou par SMS.");
        }
        if (trim($modele) === '') {
            throw new RuntimeException('Le message de relance est vide.');
        }
        if ($cibles === []) {
            throw new RuntimeException('Aucun élève à relancer.');
        }

        $lot = (string) \Illuminate\Support\Str::uuid();
        $bilan = ['relances' => 0, 'sans_destinataire' => 0, 'lot' => $lot];

        $eleves = DB::table('inscriptions as i')
            ->join('eleves as e', 'e.id', '=', 'i.eleve_id')
            ->leftJoin('classes as c', 'c.id', '=', 'i.classe_id')
            ->leftJoin('niveaux as n', 'n.id', '=', 'i.niveau_id')
            ->whereIn('i.id', array_keys($cibles))
            ->get(['i.id', 'i.espace_id', 'i.annee_id', 'e.nom', 'e.prenom', 'e.matricule', 'c.libelle as classe', 'n.libelle as niveau'])
            ->keyBy('id');

        DB::transaction(function () use ($cibles, $canal, $modele, $userId, $lot, $eleves, &$bilan) {
            foreach ($cibles as $inscriptionId => $cible) {
                $eleve = $eleves[$inscriptionId] ?? null;
                if (! $eleve) {
                    continue;
                }
                $parent = DB::table('parent_eleves')->where('espace_id', $eleve->espace_id)->where('etat', 1)
                    ->orderByDesc('is_principal')->orderBy('id')->first(['email', 'telephone', 'whatsapp']);
                $destinataire = $canal === TypeRelance::EMAIL->value
                    ? (str_contains((string) $parent?->email, '@') ? trim($parent->email) : null)
                    : (trim((string) ($parent?->telephone ?: $parent?->whatsapp)) ?: null);

                $message = strtr($modele, [
                    '{eleve}'     => trim($eleve->nom . ' ' . $eleve->prenom),
                    '{matricule}' => (string) $eleve->matricule,
                    '{classe}'    => $eleve->classe ?: ($eleve->niveau ?: '—'),
                    '{montant}'   => number_format((float) $cible['montant'], 0, ',', ' '),
                    '{echeances}' => $cible['echeances'],
                    '{date}'      => now()->format('d/m/Y'),
                ]);

                $dossier = $this->dossiers->dossierOuvertPour((int) $inscriptionId);
                Relance::create([
                    'dossier_recouvrement_id' => $dossier?->id,
                    'inscription_id'          => (int) $inscriptionId,
                    'type_relance'            => $canal,
                    'date_relance'            => now()->toDateString(),
                    'contenu'                 => $message,
                    'destinataire'            => $destinataire,
                    'statut_envoi'            => $destinataire ? 'a_envoyer' : 'sans_destinataire',
                    'lot'                     => $lot,
                    'montant_du_moment'       => round((float) $cible['montant'], 2),
                    'utilisateur_id'          => $userId,
                    'annee_id'                => $eleve->annee_id,
                ]);
                $bilan['relances']++;
                if (! $destinataire) {
                    $bilan['sans_destinataire']++;
                }
            }
        });

        return $bilan;
    }

    /** Statuts d'envoi qu'on peut (re)tenter. */
    public const A_ENVOYER = ['a_envoyer', 'echec'];

    /**
     * Envoi réel des relances sélectionnées. Désactivé tant que
     * finances.relances_envoi_actif vaut false (développement) : rien n'est
     * envoyé, messages et statuts restent tels quels. Activé, l'email part par
     * la configuration MAIL_* ; le SMS échoue tant qu'aucune passerelle n'est
     * branchée.
     *
     * @param  array<int, int>  $ids
     * @return array{envoyes:int, echecs:int, ignores:int}
     */
    public function envoyer(array $ids): array
    {
        if (! config('finances.relances_envoi_actif')) {
            throw new RuntimeException(
                "L'envoi réel des emails et des SMS n'est pas encore activé : les relances restent « à envoyer » "
                . "(messages et statuts conservés). Il sera activé une fois la messagerie et la passerelle SMS mises en place."
            );
        }

        $bilan = ['envoyes' => 0, 'echecs' => 0, 'ignores' => 0];
        foreach (Relance::whereIn('id', $ids)->where('etat', 1)->get() as $relance) {
            if (! in_array($relance->statut_envoi, self::A_ENVOYER, true) || ! $relance->destinataire) {
                $bilan['ignores']++;
                continue;
            }
            try {
                if ($relance->type_relance !== TypeRelance::EMAIL) {
                    throw new RuntimeException('Aucune passerelle SMS configurée.');
                }
                \Illuminate\Support\Facades\Mail::raw($relance->contenu, fn ($m) => $m->to($relance->destinataire)
                    ->subject('Relance — École Internationale Mariam'));
                $relance->update(['statut_envoi' => 'envoye']);
                $bilan['envoyes']++;
            } catch (\Throwable $e) {
                $relance->update(['statut_envoi' => 'echec']);
                $bilan['echecs']++;
            }
        }

        return $bilan;
    }

    /** Change le statut d'un dossier (contentieux, irrécouvrable...). */
    public function changerStatut(int $dossierId, string $nouveauStatut, ?string $motif = null): DossierRecouvrement
    {
        $dossier = $this->dossiers->findOrFail($dossierId);

        $statut = DossierStatut::from($nouveauStatut);

        $payload = ['statut' => $statut->value];

        if ($statut->isClos()) {
            $payload['date_cloture']  = now()->toDateString();
            $payload['motif_cloture'] = $motif;
        }

        $this->dossiers->update($dossier->id, $payload);

        return $dossier->refresh();
    }
}