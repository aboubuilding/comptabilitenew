<?php
namespace App\Services;

use App\Models\AbonnementBus;
use App\Models\Annee;
use App\Models\Inscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusService
{
    /**
     * Récupère l'année scolaire en cours depuis la session
     */
    protected function getCurrentAnnee(): ?Annee
    {
        $anneeId = session()->get('LoginUser')['annee_id'] ?? null;
        if (!$anneeId) return null;
        return Annee::find($anneeId);
    }

    /**
     * Calcule le nombre de mois et le montant total dû
     */
    public function calculerMensualites(string $dateDebut, float $montantMensuel, ?Annee $annee = null): array
    {
        $annee = $annee ?? $this->getCurrentAnnee();
        if (!$annee || !$annee->date_fin) {
            throw new \Exception("Année scolaire non définie ou date de fin manquante.");
        }

        $debut = Carbon::parse($dateDebut);
        $finAnnee = Carbon::parse($annee->date_fin)->endOfMonth();

        if ($debut->greaterThan($finAnnee)) {
            return ['nb_mois' => 0, 'total_du' => 0];
        }

        $nbMois = $debut->diffInMonths($finAnnee) + 1;
        $totalDu = $nbMois * $montantMensuel;

        return ['nb_mois' => $nbMois, 'total_du' => round($totalDu, 2)];
    }

    /**
     * Crée un abonnement bus pour une inscription
     */
    public function inscrireBus(int $inscriptionId, string $dateDebut, float $montantMensuel): AbonnementBus
    {
        $annee = $this->getCurrentAnnee();
        if (!$annee) {
            throw new \Exception("Année scolaire non trouvée en session.");
        }

        $inscription = Inscription::with('eleve')->findOrFail($inscriptionId);
        if ($inscription->annee_id != $annee->id) {
            throw new \Exception("L'inscription n'appartient pas à l'année en cours.");
        }

        // Vérifier si déjà inscrit au bus cette année
        $existant = AbonnementBus::where('inscription_id', $inscriptionId)
            ->where('statut', 1)
            ->first();
        if ($existant) {
            throw new \Exception("Cet élève a déjà un abonnement bus actif pour cette année.");
        }

        $calcul = $this->calculerMensualites($dateDebut, $montantMensuel, $annee);
        if ($calcul['nb_mois'] == 0) {
            throw new \Exception("La date d'inscription est après la fin de l'année scolaire.");
        }

        $dateFin = Carbon::parse($annee->date_fin)->endOfMonth();

        return DB::transaction(function () use ($inscription, $annee, $dateDebut, $dateFin, $montantMensuel, $calcul) {
            return AbonnementBus::create([
                'inscription_id'   => $inscription->id,
                'date_debut'       => $dateDebut,
                'date_fin'         => $dateFin,
                'montant_mensuel'  => $montantMensuel,
                'nombre_mois'      => $calcul['nb_mois'],
                'montant_total_du' => $calcul['total_du'],
                'statut'           => 1,
            ]);
        });
    }

    /**
     * Abandonner un abonnement bus
     */
    public function abandonnerBus(int $abonnementId, string $motif, int $userId): void
    {
        $abonnement = AbonnementBus::findOrFail($abonnementId);
        if ($abonnement->statut != 1) {
            throw new \Exception("Cet abonnement est déjà abandonné.");
        }

        $abonnement->statut = 0;
        $abonnement->date_abandon = now();
        $abonnement->motif_abandon = $motif;
        $abonnement->abandonne_par = $userId;
        $abonnement->save();
    }

    /**
     * Liste des inscrits au bus pour l'année en cours
     */
    public function listeInscritsBus(array $filters = []): array
    {
        $annee = $this->getCurrentAnnee();
        if (!$annee) {
            return ['data' => collect(), 'aggregates' => [], 'pagination' => []];
        }

        $query = AbonnementBus::with(['inscription.eleve', 'inscription.classe', 'inscription.niveau'])
            ->whereHas('inscription', fn($q) => $q->where('annee_id', $annee->id))
            ->where('statut', 1);

        // Filtres
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
            'total_paye'  => $abonnements->sum(function ($ab) { return $ab->montant_paye; }),
            'total_reste' => $abonnements->sum(function ($ab) { return $ab->montant_reste; }),
            'nombre_inscrits' => $abonnements->total(),
        ];

        return [
            'data'       => $data,
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $abonnements->currentPage(),
                'last_page'    => $abonnements->lastPage(),
                'per_page'     => $abonnements->perPage(),
                'total'        => $abonnements->total(),
            ]
        ];
    }

    /**
     * Récupère un abonnement avec ses détails
     */
    public function getAbonnement(int $id): AbonnementBus
    {
        return AbonnementBus::with(['inscription.eleve', 'inscription.classe', 'detailsPaiement'])->findOrFail($id);
    }
}