<?php

namespace App\Services;

use App\Repositories\Eloquent\AbonnementBusRepository;
use App\Repositories\Eloquent\InscriptionRepository;
use App\Repositories\Eloquent\AnneeRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BusService extends BaseService
{
    protected string $entityName = 'Abonnement bus';
    protected AbonnementBusRepository $abonnementRepo;
    protected InscriptionRepository $inscriptionRepo;
    protected AnneeRepository $anneeRepo;

    public function __construct(
        AbonnementBusRepository $abonnementRepo,
        InscriptionRepository $inscriptionRepo,
        AnneeRepository $anneeRepo
    ) {
        parent::__construct($abonnementRepo);
        $this->abonnementRepo = $abonnementRepo;
        $this->inscriptionRepo = $inscriptionRepo;
        $this->anneeRepo = $anneeRepo;
    }

    /**
     * Récupère l'année scolaire en cours depuis la session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Récupère l'année courante (modèle)
     */
    protected function getCurrentAnnee()
    {
        $id = $this->getCurrentAnneeId();
        return $id ? $this->anneeRepo->find($id) : null;
    }

    /**
     * Calcule le nombre de mois et le montant total dû
     */
    public function calculerMensualites(string $dateDebut, float $montantMensuel, ?int $anneeId = null): array
    {
        $anneeId = $anneeId ?? $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(false, 'Année scolaire non définie');
        }
        $annee = $this->anneeRepo->find($anneeId);
        if (!$annee || !$annee->date_fin) {
            return $this->formatResponse(false, 'Année scolaire non définie ou date de fin manquante');
        }

        $debut = Carbon::parse($dateDebut);
        $finAnnee = Carbon::parse($annee->date_fin)->endOfMonth();

        if ($debut->greaterThan($finAnnee)) {
            return $this->formatResponse(true, '', ['nb_mois' => 0, 'total_du' => 0]);
        }

        $nbMois = $debut->diffInMonths($finAnnee) + 1;
        $totalDu = $nbMois * $montantMensuel;

        return $this->formatResponse(true, '', ['nb_mois' => $nbMois, 'total_du' => round($totalDu, 2)]);
    }

    /**
     * Crée un abonnement bus pour une inscription
     */
    public function inscrireBus(int $inscriptionId, string $dateDebut, float $montantMensuel): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(false, 'Année scolaire non trouvée en session.');
        }

        $annee = $this->anneeRepo->find($anneeId);
        if (!$annee) {
            return $this->formatResponse(false, 'Année scolaire introuvable.');
        }

        $inscription = $this->inscriptionRepo->with('eleve')->find($inscriptionId);
        if (!$inscription || $inscription->annee_id != $anneeId) {
            return $this->formatResponse(false, "L'inscription n'existe pas ou n'appartient pas à l'année en cours.");
        }

        // Vérifier si déjà inscrit au bus cette année
        $existant = $this->abonnementRepo->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->where('statut', 1)
            ->first();
        if ($existant) {
            return $this->formatResponse(false, 'Cet élève a déjà un abonnement bus actif pour cette année.');
        }

        $calculResult = $this->calculerMensualites($dateDebut, $montantMensuel, $anneeId);
        if (!$calculResult['success']) {
            return $calculResult;
        }
        $calcul = $calculResult['data'];
        if ($calcul['nb_mois'] == 0) {
            return $this->formatResponse(false, "La date d'inscription est après la fin de l'année scolaire.");
        }

        $dateFin = Carbon::parse($annee->date_fin)->endOfMonth();

        try {
            $abonnement = DB::transaction(function () use ($inscription, $dateDebut, $dateFin, $montantMensuel, $calcul) {
                return $this->abonnementRepo->create([
                    'inscription_id'   => $inscription->id,
                    'date_debut'       => $dateDebut,
                    'date_fin'         => $dateFin,
                    'montant_mensuel'  => $montantMensuel,
                    'nombre_mois'      => $calcul['nb_mois'],
                    'montant_total_du' => $calcul['total_du'],
                    'statut'           => 1,
                ]);
            });
            return $this->formatResponse(true, 'Abonnement bus créé avec succès', $abonnement);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Abandonner un abonnement bus
     */
    public function abandonnerBus(int $abonnementId, string $motif, int $userId): array
    {
        $abonnement = $this->abonnementRepo->find($abonnementId);
        if (!$abonnement || $abonnement->statut != 1) {
            return $this->formatResponse(false, 'Abonnement introuvable ou déjà abandonné.');
        }

        try {
            $this->abonnementRepo->update($abonnementId, [
                'statut' => 0,
                'date_abandon' => now(),
                'motif_abandon' => $motif,
                'abandonne_par' => $userId,
            ]);
            return $this->formatResponse(true, 'Abonnement abandonné');
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Liste des inscrits au bus pour l'année en cours
     */
    public function listeInscritsBus(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(false, 'Année scolaire non définie', [], ['pagination' => []]);
        }

        $query = $this->abonnementRepo->activeQuery()
            ->with(['inscription.eleve', 'inscription.classe', 'inscription.niveau'])
            ->whereHas('inscription', fn($q) => $q->where('annee_id', $anneeId))
            ->where('statut', 1);

        if (!empty($filters['eleve_search'])) {
            $search = '%' . $filters['eleve_search'] . '%';
            $query->whereHas('inscription.eleve', function ($q) use ($search) {
                $q->where('nom', 'like', $search)
                    ->orWhere('prenom', 'like', $search)
                    ->orWhere('matricule', 'like', $search);
            });
        }
        if (!empty($filters['classe_id'])) {
            $query->whereHas('inscription', fn($q) => $q->where('classe_id', $filters['classe_id']));
        }
        if (!empty($filters['niveau_id'])) {
            $query->whereHas('inscription', fn($q) => $q->where('niveau_id', $filters['niveau_id']));
        }

        $perPage = $filters['per_page'] ?? 15;
        $abonnements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $abonnements->map(function ($ab) {
            $eleve = $ab->inscription?->eleve;
            return [
                'id'                => $ab->id,
                'eleve'             => $eleve ? $eleve->nom . ' ' . $eleve->prenom : '',
                'matricule'         => $eleve?->matricule,
                'classe'            => $ab->inscription?->classe?->libelle,
                'date_debut'        => $ab->date_debut->format('d/m/Y'),
                'montant_mensuel'   => $ab->montant_mensuel,
                'nombre_mois'       => $ab->nombre_mois,
                'montant_total_du'  => $ab->montant_total_du,
                'montant_paye'      => $ab->montant_paye,
                'montant_reste'     => $ab->montant_reste,
            ];
        });

        $aggregates = [
            'total_du'    => $abonnements->sum('montant_total_du'),
            'total_paye'  => $abonnements->sum(fn($ab) => $ab->montant_paye),
            'total_reste' => $abonnements->sum(fn($ab) => $ab->montant_reste),
            'nombre_inscrits' => $abonnements->total(),
        ];

        return $this->formatResponse(true, 'Liste des inscrits bus', $data, [
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $abonnements->currentPage(),
                'last_page'    => $abonnements->lastPage(),
                'per_page'     => $abonnements->perPage(),
                'total'        => $abonnements->total(),
            ]
        ]);
    }

    /**
     * Récupère un abonnement avec ses détails
     */
    public function getAbonnement(int $id): array
    {
        try {
            $abonnement = $this->abonnementRepo->activeQuery()
                ->with(['inscription.eleve', 'inscription.classe', 'detailsPaiement'])
                ->findOrFail($id);
            return $this->formatResponse(true, '', $abonnement);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Abonnement introuvable');
        }
    }

    /**
     * Méthode utilitaire pour récupérer les montants payés et restants
     * (À adapter selon votre modèle AbonnementBus s'il a les accesseurs)
     */
    // Si nécessaire, on peut laisser cette méthode
}
