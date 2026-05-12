<?php

namespace App\Services;

use App\Repositories\Interfaces\PaiementRepositoryInterface;
use App\Repositories\Interfaces\DetailRepositoryInterface;
use App\Repositories\Interfaces\InscriptionRepositoryInterface;
use App\Repositories\Interfaces\ActiviteRepositoryInterface;
use App\Repositories\Interfaces\ProduitRepositoryInterface;
use App\Repositories\Interfaces\AbonnementBusRepositoryInterface;
use App\Repositories\Interfaces\InscriptionCantineRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementService extends BaseService
{
    protected string $entityName = 'Paiement';
    protected array $defaultSelectFields = [
        'id', 'reference', 'date_paiement', 'montant', 'statut_paiement',
        'mode_paiement', 'inscription_id', 'utilisateur_id', 'annee_id', 'etat'
    ];

    protected DetailRepositoryInterface $detailRepo;
    protected InscriptionRepositoryInterface $inscriptionRepo;
    protected ActiviteRepositoryInterface $activiteRepo;
    protected ProduitRepositoryInterface $produitRepo;
    protected AbonnementBusRepositoryInterface $abonnementBusRepo;
    protected InscriptionCantineRepositoryInterface $inscriptionCantineRepo;
    protected UserRepositoryInterface $userRepo;

    public function __construct(
        PaiementRepositoryInterface $paiementRepo,
        DetailRepositoryInterface $detailRepo,
        InscriptionRepositoryInterface $inscriptionRepo,
        ActiviteRepositoryInterface $activiteRepo,
        ProduitRepositoryInterface $produitRepo,
        AbonnementBusRepositoryInterface $abonnementBusRepo,
        InscriptionCantineRepositoryInterface $inscriptionCantineRepo,
        UserRepositoryInterface $userRepo
    ) {
        parent::__construct($paiementRepo);
        $this->detailRepo = $detailRepo;
        $this->inscriptionRepo = $inscriptionRepo;
        $this->activiteRepo = $activiteRepo;
        $this->produitRepo = $produitRepo;
        $this->abonnementBusRepo = $abonnementBusRepo;
        $this->inscriptionCantineRepo = $inscriptionCantineRepo;
        $this->userRepo = $userRepo;
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

        $user = auth()->user();
        $query = $this->repo->activeQuery()
            ->with(['inscription.eleve', 'utilisateur'])
            ->where('annee_id', $anneeId);

        if (!in_array($user->role, ['admin', 'directeur'])) {
            $query->where('utilisateur_id', $user->id);
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

    /**
     * Créer un paiement (non encaissé)
     */
    public function store(array $data, int $userId): Paiement
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            throw new \Exception('Année scolaire non définie en session.');
        }

        $inscription = $this->inscriptionRepo->with('eleve')->findOrFail($data['inscription_id']);
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

            $paiement = $this->repo->create([
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
                $this->detailRepo->create([
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

    /**
     * Valider un paiement (encaissement)
     */
    public function validerPaiement(int $paiementId, int $userId): Paiement
    {
        $paiement = $this->repo->activeQuery()->where('id', $paiementId)->firstOrFail();
        if ($paiement->statut_paiement == 1) {
            throw new \Exception('Ce paiement est déjà encaissé.');
        }

        DB::beginTransaction();
        try {
            $this->repo->update($paiementId, ['statut_paiement' => 1]);

            $this->detailRepo->activeQuery()
                ->where('paiement_id', $paiementId)
                ->update([
                    'statut_paiement' => 1,
                    'date_encaissement' => now(),
                    'caissier_id' => $userId,
                ]);

            DB::commit();
            return $this->repo->find($paiementId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Annuler un paiement (soft)
     */
    public function annulerPaiement(int $paiementId, string $motif): Paiement
    {
        $paiement = $this->repo->findOrFail($paiementId);
        if ($paiement->statut_paiement == 1) {
            // Optionnel : autoriser l'annulation même encaissé
        }
        $this->repo->update($paiementId, [
            'statut_paiement' => 2,
            'motif_suppression' => $motif,
        ]);
        $this->detailRepo->activeQuery()
            ->where('paiement_id', $paiementId)
            ->update(['statut_paiement' => 2]);

        return $this->repo->find($paiementId);
    }

    /**
     * Totaux par type pour une inscription (avec remises)
     */
    public function getTotauxParInscription(int $inscriptionId): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $inscription = $this->inscriptionRepo->findOrFail($inscriptionId);

        $prevus = [
            'inscription' => $inscription->montant_inscription_reel,
            'scolarite'   => $inscription->montant_scolarite_reel,
            'assurance'   => $inscription->montant_assurance_reel,
            'examen'      => $inscription->montant_examen_reel,
            'cantine'     => $this->getPrevuCantine($inscription, $anneeId),
            'bus'         => $this->getPrevuBus($inscription, $anneeId),
        ];

        $payeParType = $this->detailRepo->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('statut_paiement', 1)
            ->selectRaw('type_paiement, SUM(montant) as total')
            ->groupBy('type_paiement')
            ->get()
            ->keyBy('type_paiement');

        $mapping = [
            1 => 'inscription',
            2 => 'scolarite',
            10 => 'assurance',
            12 => 'examen',
            8 => 'cantine',
            7 => 'bus',
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
    // Méthodes privées (vérifications, calculs)
    // ─────────────────────────────────────────────────────────────

    private function verifierPlafond($inscription, array $detail, int $anneeId): void
    {
        $type = $detail['type_paiement'];
        $montant = $detail['montant'];

        $dejaPaye = $this->getDejaPayePourType($inscription->id, $type, $anneeId);

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
                $abonnement = $this->abonnementBusRepo->findOrFail($detail['abonnement_bus_id']);
                $remise = $inscription->taux_remise ?? 0;
                $totalDu = round($abonnement->montant_total_du * (1 - $remise / 100), 2);
                break;
            case 8: // Cantine
                $cantine = $this->inscriptionCantineRepo->findOrFail($detail['inscription_cantine_id']);
                $remise = $inscription->taux_remise ?? 0;
                $totalDu = round($cantine->montant_total_du * (1 - $remise / 100), 2);
                break;
            case 6: // Activité
                $activiteId = $detail['activite_id'] ?? null;
                if (!$activiteId) throw new \Exception('Activité non spécifiée');
                $activite = $this->activiteRepo->find($activiteId);
                if (!$activite) throw new \Exception('Activité introuvable');
                $totalDu = $activite->montant;
                break;
            case 4: // Produit
                // Vérifier stock si produit et quantité
                if (isset($detail['produit_id']) && isset($detail['quantite'])) {
                    $produit = $this->produitRepo->find($detail['produit_id']);
                    if ($produit && $produit->quantite_stock < $detail['quantite']) {
                        throw new \Exception("Stock insuffisant pour le produit {$produit->libelle}");
                    }
                }
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

    private function getPrevuCantine($inscription, int $anneeId): float
    {
        $cantine = $this->inscriptionCantineRepo->activeQuery()
            ->where('inscription_id', $inscription->id)
            ->where('annee_id', $anneeId)
            ->first();
        if (!$cantine) return 0;
        $remise = $inscription->taux_remise ?? 0;
        return round($cantine->montant_total_du * (1 - $remise / 100), 2);
    }

    private function getPrevuBus($inscription, int $anneeId): float
    {
        $bus = $this->abonnementBusRepo->activeQuery()
            ->where('inscription_id', $inscription->id)
            ->where('annee_id', $anneeId)
            ->first();
        if (!$bus) return 0;
        $remise = $inscription->taux_remise ?? 0;
        return round($bus->montant_total_du * (1 - $remise / 100), 2);
    }

    private function getDejaPayePourType(int $inscriptionId, int $type, int $anneeId): float
    {
        return $this->detailRepo->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->where('annee_id', $anneeId)
            ->where('type_paiement', $type)
            ->where('statut_paiement', 1)
            ->sum('montant');
    }

    private function genererReference(): string
    {
        $prefix = 'PAY-' . date('Ymd');
        $last = $this->repo->getModel()->newQuery()
            ->where('reference', 'like', $prefix . '%')
            ->max('reference');
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
