<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\DetailPaiement;
use App\Models\Inscription;
use App\Models\Activite;
use App\Models\Produit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    /**
     * Récupère l'année en session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des paiements selon le rôle
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

        // Filtre par rôle
        if (!in_array($this->user->role, ['admin', 'directeur'])) {
            $query->where('utilisateur_id', $this->user->id);
        }

        // Filtres
        if (!empty($filters['statut_paiement'])) {
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
            $query->whereHas('inscription.eleve', function ($q) use ($search) {
                $q->where('nom', 'like', $search)->orWhere('prenom', 'like', $search);
            })->orWhere('reference', 'like', $search);
        }

        $perPage = $filters['per_page'] ?? 15;
        $paiements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $paiements->map(function ($paiement) {
            return [
                'id'            => $paiement->id,
                'reference'     => $paiement->reference,
                'date_paiement' => $paiement->date_paiement,
                'montant'       => $paiement->montant,
                'statut'        => $paiement->statut_paiement,
                'statut_label'  => $this->getStatutLabel($paiement->statut_paiement),
                'mode_paiement' => $paiement->mode_paiement,
                'eleve_nom'     => $paiement->inscription?->eleve?->nom . ' ' . $paiement->inscription?->eleve?->prenom,
                'utilisateur_nom' => $paiement->utilisateur?->name,
                'payeur'        => $paiement->payeur,
            ];
        });

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

    /**
     * Liste des paiements en attente (non encaissés)
     */
    public function listPaiementsEnAttente(array $filters = []): array
    {
        $filters['statut_paiement'] = 0; // ou null selon votre convention
        return $this->listPaiements($filters);
    }

    /**
     * Créer un paiement avec ses détails
     */
    public function store(array $data, int $userId): Paiement
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            throw new \Exception('Année scolaire non définie en session.');
        }

        $inscription = Inscription::with('eleve')->findOrFail($data['inscription_id']);

        // Vérifier que l'inscription est pour l'année courante
        if ($inscription->annee_id != $anneeId) {
            throw new \Exception('Cette inscription ne correspond pas à l\'année en cours.');
        }

        DB::beginTransaction();
        try {
            // Vérifier les plafonds pour chaque détail
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

            // Générer référence unique
            $reference = $this->genererReference();

            // Créer paiement
            $paiement = Paiement::create([
                'reference'        => $reference,
                'payeur'           => $data['payeur'] ?? null,
                'telephone_payeur' => $data['telephone_payeur'] ?? null,
                'date_paiement'    => $data['date_paiement'],
                'statut_paiement'  => 0, // en attente
                'mode_paiement'    => $data['mode_paiement'],
                'inscription_id'   => $inscription->id,
                'utilisateur_id'   => $userId,
                'annee_id'         => $anneeId,
                'montant'          => $data['montant'],
                'etat'             => 1,
            ]);

            // Créer les détails
            foreach ($data['details'] as $detail) {
                DetailPaiement::create([
                    'montant'          => $detail['montant'],
                    'libelle'          => $detail['libelle'],
                    'paiement_id'      => $paiement->id,
                    'type_paiement'    => $detail['type_paiement'],
                    'inscription_id'   => $inscription->id,
                    'frais_ecole_id'   => $detail['frais_ecole_id'] ?? null,
                    'statut_paiement'  => 0,
                    'annee_id'         => $anneeId,
                    'date_paiement'    => $data['date_paiement'],
                    'date_encaissement'=> null,
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

    /**
     * Valider/encaisser un paiement
     */
    public function validerPaiement(int $paiementId, int $userId): Paiement
    {
        $paiement = Paiement::where('id', $paiementId)
            ->where('etat', 1)
            ->firstOrFail();

        if ($paiement->statut_paiement == 1) {
            throw new \Exception('Ce paiement est déjà encaissé.');
        }

        DB::beginTransaction();
        try {
            $paiement->statut_paiement = 1;
            $paiement->save();

            // Mettre à jour les détails
            DetailPaiement::where('paiement_id', $paiementId)
                ->update([
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

    /**
     * Annuler un paiement (soft delete)
     */
    public function annulerPaiement(int $paiementId, string $motif): Paiement
    {
        $paiement = Paiement::findOrFail($paiementId);
        $paiement->statut_paiement = 2;
        $paiement->motif_suppression = $motif;
        $paiement->save();

        // Détails aussi annulés
        DetailPaiement::where('paiement_id', $paiementId)
            ->update(['statut_paiement' => 2]);

        return $paiement;
    }

    /**
     * Obtenir les totaux par type de paiement pour une inscription donnée (année courante)
     */
    public function getTotauxParInscription(int $inscriptionId): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $inscription = Inscription::with('eleve')->findOrFail($inscriptionId);

        // Montants prévus depuis l'inscription
        $prevus = [
            'scolarite'   => $inscription->frais_scolarite ?? 0,
            'cantine'     => $inscription->frais_cantine ?? 0,
            'bus'         => $inscription->frais_bus ?? 0,
            'inscription' => $inscription->frais_inscription ?? 0,
            'examen'      => $inscription->frais_examen ?? 0,
        ];

        // Paiements déjà effectués (encaissés)
        $paye = DetailPaiement::where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('statut_paiement', 1)
            ->selectRaw('type_paiement, SUM(montant) as total')
            ->groupBy('type_paiement')
            ->get()
            ->keyBy('type_paiement');

        // Mappage type_paiement => libellé
        $types = [
            1 => 'scolarite',
            2 => 'cantine',
            3 => 'bus',
            4 => 'inscription',
            5 => 'examen',
            6 => 'activite',
            7 => 'produit',
            8 => 'autre',
        ];

        $result = [];
        foreach ($types as $code => $nom) {
            $prev = $prevus[$nom] ?? 0;
            $pay = $paye[$code]->total ?? 0;
            $reste = $prev - $pay;
            $result[$nom] = [
                'type_code' => $code,
                'prevu'     => $prev,
                'paye'      => $pay,
                'reste'     => max($reste, 0),
            ];
        }

        // Ajout des activités et produits (pas de prévu fixe, on calcule à la demande)
        // Pour les activités, on peut récupérer les montants par niveau
        $activitesPayees = DetailPaiement::where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('type_paiement', 6)
            ->whereNotNull('frais_ecole_id') // ou activite_id
            ->sum('montant');

        $result['activite']['paye'] = $activitesPayees;
        $result['activite']['prevu'] = 0; // à déterminer par niveau
        $result['activite']['reste'] = 0;

        $produitsPayes = DetailPaiement::where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('type_paiement', 7)
            ->sum('montant');
        $result['produit']['paye'] = $produitsPayes;
        $result['produit']['prevu'] = 0;
        $result['produit']['reste'] = 0;

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

        switch ($type) {
            case 1: // scolarité
            case 2: // cantine
            case 3: // bus
            case 4: // inscription
            case 5: // examen
                $prevu = $this->getPrevuInscription($inscription, $type);
                if ($dejaPaye + $montant > $prevu) {
                    throw ValidationException::withMessages([
                        'details' => "Le montant dépasse le solde dû pour ce type de frais. Restant : " . ($prevu - $dejaPaye)
                    ]);
                }
                break;
            case 6: // activité
                $activiteId = $detail['activite_id'] ?? null;
                if (!$activiteId) {
                    throw new \Exception('Activité non spécifiée');
                }
                $activite = Activite::where('id', $activiteId)
                    ->where('annee_id', $anneeId)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->first();
                if (!$activite) {
                    throw new \Exception('Activité non trouvée pour ce niveau');
                }
                if ($dejaPaye + $montant > $activite->montant) {
                    throw ValidationException::withMessages([
                        'details' => "Montant activité dépasse le tarif fixé ({$activite->montant})"
                    ]);
                }
                break;
            case 7: // produit - pas de plafond (libre)
                // pas de vérification
                break;
            case 8: // autre
                // pas de vérification
                break;
        }
    }

    private function getPrevuInscription(Inscription $inscription, int $type): float
    {
        return match ($type) {
            1 => $inscription->frais_scolarite ?? 0,
            2 => $inscription->frais_cantine ?? 0,
            3 => $inscription->frais_bus ?? 0,
            4 => $inscription->frais_inscription ?? 0,
            5 => $inscription->frais_examen ?? 0,
            default => 0,
        };
    }

    private function getDejaPayePourType(int $inscriptionId, int $type, int $anneeId): float
    {
        return DetailPaiement::where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('type_paiement', $type)
            ->where('statut_paiement', 1) // seulement encaissés
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