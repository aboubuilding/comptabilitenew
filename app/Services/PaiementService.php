<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Detail;
use App\Models\Inscription;
use App\Models\Activite;
use App\Models\Produit;
use App\Models\AbonnementBus;
use App\Models\InscriptionCantine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des paiements avec filtres
     */
    public function listPaiements(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return ['data' => collect(), 'pagination' => []];
        }

        $query = Paiement::with(['inscription.eleve', 'utilisateur'])
            ->where('annee_id', $anneeId)
            ->where('etat', 1);

        if (!in_array($this->user->role, ['admin', 'directeur'])) {
            $query->where('utilisateur_id', $this->user->id);
        }

        if (isset($filters['statut_paiement']) && $filters['statut_paiement'] !== '') {
            $query->where('statut_paiement', $filters['statut_paiement']);
        }
        if (!empty($filters['mode_paiement'])) {
            $query->where('mode_paiement', $filters['mode_paiement']);
        }
        if (!empty($filters['inscription_id'])) {
            $query->where('inscription_id', $filters['inscription_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', $search)
                    ->orWhereHas('inscription.eleve', fn($sq) => $sq->where('nom', 'like', $search)->orWhere('prenom', 'like', $search));
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $paiements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $paiements->map(fn($p) => [
            'id'            => $p->id,
            'reference'     => $p->reference,
            'date_paiement' => $p->date_paiement,
            'montant'       => $p->montant,
            'statut'        => $p->statut_paiement,
            'statut_label'  => $this->getStatutLabel($p->statut_paiement),
            'mode_paiement' => $p->mode_paiement,
            'eleve_nom'     => $p->inscription?->eleve?->nom . ' ' . $p->inscription?->eleve?->prenom,
            'utilisateur_nom'=> $p->utilisateur?->name,
            'payeur'        => $p->payeur,
        ]);

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $paiements->currentPage(),
                'last_page'    => $paiements->lastPage(),
                'per_page'     => $paiements->perPage(),
                'total'        => $paiements->total(),
            ]
        ];
    }

    public function listPaiementsEnAttente(array $filters = []): array
    {
        $filters['statut_paiement'] = 0;
        return $this->listPaiements($filters);
    }

    public function store(array $data, int $userId): Paiement
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            throw new \Exception('Année scolaire non définie en session.');
        }

        $inscription = Inscription::with('eleve')->findOrFail($data['inscription_id']);
        if ($inscription->annee_id != $anneeId) {
            throw new \Exception('Cette inscription ne correspond pas à l\'année en cours.');
        }

        DB::beginTransaction();
        try {
            $totalDetails = 0;
            foreach ($data['details'] as $detail) {
                $totalDetails += $detail['montant'];
                $this->verifierPlafond($inscription, $detail, $anneeId);
            }

            if (abs($totalDetails - $data['montant']) > 0.01) {
                throw ValidationException::withMessages([
                    'montant' => 'La somme des détails ne correspond pas au montant total.',
                ]);
            }

            $reference = $this->genererReference();

            $paiement = Paiement::create([
                'reference'        => $reference,
                'payeur'           => $data['payeur'] ?? null,
                'telephone_payeur' => $data['telephone_payeur'] ?? null,
                'date_paiement'    => $data['date_paiement'],
                'statut_paiement'  => 0,
                'mode_paiement'    => $data['mode_paiement'],
                'inscription_id'   => $inscription->id,
                'utilisateur_id'   => $userId,
                'annee_id'         => $anneeId,
                'montant'          => $data['montant'],
                'etat'             => 1,
            ]);

            foreach ($data['details'] as $detail) {
                Detail::create([
                    'montant'          => $detail['montant'],
                    'libelle'          => $detail['libelle'],
                    'paiement_id'      => $paiement->id,
                    'type_paiement'    => $detail['type_paiement'],
                    'inscription_id'   => $inscription->id,
                    'frais_ecole_id'   => $detail['frais_ecole_id'] ?? null,
                    'produit_id'       => $detail['produit_id'] ?? null,
                    'service_id'       => $detail['service_id'] ?? null,
                    'activite_id'      => $detail['activite_id'] ?? null,
                    'abonnement_bus_id'=> $detail['abonnement_bus_id'] ?? null,
                    'inscription_cantine_id'=> $detail['inscription_cantine_id'] ?? null,
                    'statut_paiement'  => 0,
                    'annee_id'         => $anneeId,
                    'date_paiement'    => $data['date_paiement'],
                    'etat'             => 1,
                ]);
            }

            DB::commit();
            return $paiement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function validerPaiement(int $paiementId, int $userId): Paiement
    {
        $paiement = Paiement::where('id', $paiementId)->where('etat', 1)->firstOrFail();
        if ($paiement->statut_paiement == 1) {
            throw new \Exception('Ce paiement est déjà encaissé.');
        }

        DB::beginTransaction();
        try {
            $paiement->statut_paiement = 1;
            $paiement->save();

            Detail::where('paiement_id', $paiementId)->update([
                'statut_paiement' => 1,
                'date_encaissement' => now(),
                'caissier_id' => $userId,
            ]);

            DB::commit();
            return $paiement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function annulerPaiement(int $paiementId, string $motif): Paiement
    {
        $paiement = Paiement::findOrFail($paiementId);
        if ($paiement->statut_paiement == 1) {
            // On peut autoriser l'annulation d'un paiement encaissé ? Optionnel
            // Ici on laisse, il faudrait peut-être restituer le stock etc.
        }
        $paiement->statut_paiement = 2;
        $paiement->motif_suppression = $motif;
        $paiement->save();

        Detail::where('paiement_id', $paiementId)->update(['statut_paiement' => 2]);

        return $paiement;
    }

    /**
     * Totaux par type pour une inscription (intégrant les remises)
     */
    public function getTotauxParInscription(int $inscriptionId): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $inscription = Inscription::findOrFail($inscriptionId);

        // Montants prévus après remise (si applicable)
        $prevus = [
            'inscription' => $inscription->montant_inscription_reel,
            'scolarite'   => $inscription->montant_scolarite_reel,
            'assurance'   => $inscription->montant_assurance_reel,
            'examen'      => $inscription->montant_examen_reel,
            'cantine'     => $this->getPrevuCantine($inscription, $anneeId),
            'bus'         => $this->getPrevuBus($inscription, $anneeId),
        ];

        $payeParType = Detail::where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('statut_paiement', 1)
            ->selectRaw('type_paiement, SUM(montant) as total')
            ->groupBy('type_paiement')
            ->get()
            ->keyBy('type_paiement');

        // Mapping type_paiement => nom
        $mapping = [
            1 => 'inscription',
            2 => 'scolarite',
            10 => 'assurance',
            12 => 'examen',
            8 => 'cantine',   // type 8 = cantine
            7 => 'bus',       // type 7 = bus
            6 => 'activite',
            4 => 'produit',
            3 => 'service',
            9 => 'autre',
        ];

        $result = [];
        foreach ($mapping as $code => $nom) {
            $prev = $prevus[$nom] ?? 0;
            $pay = $payeParType[$code]->total ?? 0;
            $reste = max($prev - $pay, 0);
            $result[$nom] = [
                'type_code' => $code,
                'prevu'     => round($prev, 2),
                'paye'      => round($pay, 2),
                'reste'     => round($reste, 2),
            ];
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────

    private function verifierPlafond(Inscription $inscription, array $detail, int $anneeId): void
    {
        $type = $detail['type_paiement'];
        $montant = $detail['montant'];

        $dejaPaye = $this->getDejaPayePourType($inscription->id, $type, $anneeId);

        // Déterminer le montant dû (plafond) selon le type
        $totalDu = null;
        switch ($type) {
            case 1: // Frais inscription
                $totalDu = $inscription->montant_inscription_reel;
                break;
            case 2: // Scolarité
                $totalDu = $inscription->montant_scolarite_reel;
                break;
            case 10: // Assurance
                $totalDu = $inscription->montant_assurance_reel;
                break;
            case 12: // Examen
                $totalDu = $inscription->montant_examen_reel;
                break;
            case 7: // Bus
                $abonnement = AbonnementBus::findOrFail($detail['abonnement_bus_id']);
                $remise = $inscription->taux_remise ?? 0;
                $totalDu = round($abonnement->montant_total_du * (1 - $remise / 100), 2);
                break;
            case 8: // Cantine
                $cantine = InscriptionCantine::findOrFail($detail['inscription_cantine_id']);
                $remise = $inscription->taux_remise ?? 0;
                $totalDu = round($cantine->montant_total_du * (1 - $remise / 100), 2);
                break;
            case 6: // Activité
                $activiteId = $detail['activite_id'] ?? null;
                if (!$activiteId) throw new \Exception('Activité non spécifiée');
                $activite = Activite::find($activiteId);
                if (!$activite) throw new \Exception('Activité introuvable');
                $totalDu = $activite->montant;
                break;
            case 4: // Produit
                // Pas de plafond global, mais vérification de stock à faire séparément
                if (isset($detail['produit_id']) && isset($detail['quantite'])) {
                    $produit = Produit::find($detail['produit_id']);
                    if ($produit && $produit->quantite_stock < $detail['quantite']) {
                        throw new \Exception("Stock insuffisant pour le produit {$produit->libelle}");
                    }
                }
                $totalDu = null; // pas de plafond monétaire
                break;
            case 3: // Service
            case 5: // Location livre (à définir)
            case 9: // Autre
                // Pas de plafond
                $totalDu = null;
                break;
            default:
                $totalDu = null;
        }

        if ($totalDu !== null && ($dejaPaye + $montant) > ($totalDu + 0.01)) {
            $reste = max($totalDu - $dejaPaye, 0);
            throw ValidationException::withMessages([
                'details' => "Montant dépasse le solde dû pour ce type. Reste autorisé : {$reste}"
            ]);
        }
    }

    private function getPrevuCantine(Inscription $inscription, int $anneeId): float
    {
        $cantine = InscriptionCantine::where('inscription_id', $inscription->id)
            ->where('annee_id', $anneeId)
            ->first();
        if (!$cantine) return 0;
        $remise = $inscription->taux_remise ?? 0;
        return round($cantine->montant_total_du * (1 - $remise / 100), 2);
    }

    private function getPrevuBus(Inscription $inscription, int $anneeId): float
    {
        $bus = AbonnementBus::where('inscription_id', $inscription->id)
            ->where('annee_id', $anneeId)
            ->first();
        if (!$bus) return 0;
        $remise = $inscription->taux_remise ?? 0;
        return round($bus->montant_total_du * (1 - $remise / 100), 2);
    }

    private function getDejaPayePourType(int $inscriptionId, int $type, int $anneeId): float
    {
        return Detail::where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('type_paiement', $type)
            ->where('statut_paiement', 1)
            ->sum('montant');
    }

    private function genererReference(): string
    {
        $prefix = 'PAY-' . date('Ymd');
        $last = Paiement::where('reference', 'like', $prefix . '%')->max('reference');
        if ($last) {
            $num = intval(substr($last, -4)) + 1;
        } else {
            $num = 1;
        }
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    private function getStatutLabel(?int $statut): string
    {
        return match ($statut) {
            0 => 'En attente',
            1 => 'Encaissé',
            2 => 'Annulé',
            default => 'Inconnu',
        };
    }
}
