<?php
namespace App\Services;

use App\Models\InscriptionCantine;
use App\Models\Annee;
use App\Models\Inscription;
use App\Models\FraisEcole;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CantineService
{
    const TYPE_PAIEMENT_CANTINE = 2;

    protected function getCurrentAnnee(): ?Annee
    {
        $anneeId = session()->get('LoginUser')['annee_id'] ?? null;
        return $anneeId ? Annee::find($anneeId) : null;
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
     * Inscription à la cantine
     */
    public function inscrireCantine(int $inscriptionId, string $dateDebut, ?int $fraisEcoleId = null): InscriptionCantine
    {
        $annee = $this->getCurrentAnnee();
        if (!$annee) throw new \Exception("Année scolaire non trouvée.");

        $inscription = Inscription::findOrFail($inscriptionId);
        if ($inscription->annee_id != $annee->id) {
            throw new \Exception("L'inscription n'appartient pas à l'année en cours.");
        }

        // Vérifier si déjà inscrit
        $existant = InscriptionCantine::where('inscription_id', $inscriptionId)
            ->where('statut', 1)
            ->first();
        if ($existant) throw new \Exception("Cet élève a déjà une inscription cantine active.");

        // Récupérer le tarif mensuel depuis frais_ecoles (cantine) ou utiliser un montant passé
        $montantMensuel = null;
        if ($fraisEcoleId) {
            $frais = FraisEcole::find($fraisEcoleId);
            if (!$frais) throw new \Exception("Offre de cantine non trouvée.");
            $montantMensuel = $frais->montant;
        } else {
            // Fallback : chercher le frais cantine actif pour l'année/niveau
            $frais = FraisEcole::where('annee_id', $annee->id)
                ->where('niveau_id', $inscription->niveau_id)
                ->where('type_paiement', self::TYPE_PAIEMENT_CANTINE)
                ->first();
            if (!$frais) throw new \Exception("Aucun tarif cantine défini pour ce niveau.");
            $montantMensuel = $frais->montant;
            $fraisEcoleId = $frais->id;
        }

        $calcul = $this->calculerMensualites($dateDebut, $montantMensuel, $annee);
        if ($calcul['nb_mois'] == 0) throw new \Exception("Date d'inscription postérieure à la fin de l'année.");

        $dateFin = Carbon::parse($annee->date_fin)->endOfMonth();

        return DB::transaction(function () use ($inscription, $fraisEcoleId, $dateDebut, $dateFin, $montantMensuel, $calcul) {
            return InscriptionCantine::create([
                'inscription_id'   => $inscription->id,
                'frais_ecole_id'   => $fraisEcoleId,
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
     * Abandon cantine
     */
    public function abandonnerCantine(int $id, string $motif, int $userId): void
    {
        $insc = InscriptionCantine::findOrFail($id);
        if ($insc->statut != 1) throw new \Exception("Déjà abandonné.");
        $insc->statut = 0;
        $insc->date_abandon = now();
        $insc->motif_abandon = $motif;
        $insc->abandonne_par = $userId;
        $insc->save();
    }

    /**
     * Liste des inscrits cantine avec montants
     */
    public function listeInscrits(array $filters = []): array
    {
        $annee = $this->getCurrentAnnee();
        if (!$annee) return ['data' => collect(), 'aggregates' => [], 'pagination' => []];

        $query = InscriptionCantine::with(['inscription.eleve', 'inscription.classe', 'inscription.niveau'])
            ->whereHas('inscription', fn($q) => $q->where('annee_id', $annee->id))
            ->where('statut', 1);

        if (!empty($filters['eleve_search'])) {
            $search = '%' . $filters['eleve_search'] . '%';
            $query->whereHas('inscription.eleve', fn($q) => $q->where('nom', 'like', $search)->orWhere('prenom', 'like', $search));
        }
        if (!empty($filters['classe_id'])) {
            $query->whereHas('inscription', fn($q) => $q->where('classe_id', $filters['classe_id']));
        }
        if (!empty($filters['niveau_id'])) {
            $query->whereHas('inscription', fn($q) => $q->where('niveau_id', $filters['niveau_id']));
        }

        $perPage = $filters['per_page'] ?? 15;
        $inscrits = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $inscrits->map(fn($i) => [
            'id'                => $i->id,
            'eleve'             => $i->inscription->eleve->nom . ' ' . $i->inscription->eleve->prenom,
            'matricule'         => $i->inscription->eleve->matricule,
            'classe'            => $i->inscription->classe?->libelle,
            'date_debut'        => $i->date_debut->format('d/m/Y'),
            'montant_mensuel'   => $i->montant_mensuel,
            'nombre_mois'       => $i->nombre_mois,
            'montant_total_du'  => $i->montant_total_du,
            'montant_paye'      => $i->montant_paye,
            'montant_reste'     => $i->montant_reste,
        ]);

        $aggregates = [
            'total_du'    => $inscrits->sum('montant_total_du'),
            'total_paye'  => $inscrits->sum(fn($i) => $i->montant_paye),
            'total_reste' => $inscrits->sum(fn($i) => $i->montant_reste),
            'total_inscrits' => $inscrits->total(),
        ];

        return [
            'data' => $data,
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $inscrits->currentPage(),
                'last_page'    => $inscrits->lastPage(),
                'per_page'     => $inscrits->perPage(),
                'total'        => $inscrits->total(),
            ]
        ];
    }

    public function getInscription(int $id): InscriptionCantine
    {
        return InscriptionCantine::with(['inscription.eleve', 'inscription.classe', 'detailsPaiement'])->findOrFail($id);
    }
}