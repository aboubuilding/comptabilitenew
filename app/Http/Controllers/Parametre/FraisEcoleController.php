<?php

namespace App\Http\Controllers\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\FraisEcoleRequest;
use App\Http\Requests\PlanEcheancierRequest;
use App\Models\FraisEcole;
use App\Models\Annee;
use App\Models\Niveau;
use App\Services\FraisEcoleService;
use App\Repositories\Interfaces\FraisEcoleRepositoryInterface;
use App\Repositories\Interfaces\PlanEcheancierRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class FraisEcoleController extends Controller
{
    protected FraisEcoleRepositoryInterface $repository;
    protected PlanEcheancierRepositoryInterface $planRepository;
    protected FraisEcoleService $service;
    protected AuthService $authService;

    public function __construct(
        FraisEcoleRepositoryInterface $repository,
        PlanEcheancierRepositoryInterface $planRepository,
        FraisEcoleService $service,
        AuthService $authService
    ) {
        $this->repository = $repository;
        $this->planRepository = $planRepository;
        $this->service = $service;
        $this->authService = $authService;
    }

    /**
     * Liste des frais d'école
     */
    public function index(Request $request)
    {
        // Récupérer l'année en cours depuis la session
        $anneeCourante = $this->authService->getCurrentYear();

        if (!$anneeCourante) {
            return redirect()->back()->with('error', 'Aucune année scolaire active. Veuillez contacter l\'administrateur.');
        }

        $anneeId = $anneeCourante->id;

        // Récupérer les données pour les filtres
        $niveaux = Niveau::active()->orderBy('libelle')->get();
        $plans = $this->planRepository->getActivePlans();

        return view('admin.frais-ecoles.index', compact('niveaux', 'plans', 'anneeCourante'));
    }

    /**
     * Récupérer les données pour DataTables
     */
    public function getData(Request $request)
    {
        // Récupérer l'année en cours depuis la session
        $anneeCourante = $this->authService->getCurrentYear();

        if (!$anneeCourante) {
            return response()->json(['error' => 'Aucune année scolaire active'], 400);
        }

        $query = FraisEcole::with(['niveau', 'annee', 'planEcheancier'])
            ->where('annee_id', $anneeCourante->id);

        // Filtres
        if ($request->has('type_paiement') && $request->type_paiement != '') {
            $query->where('type_paiement', $request->type_paiement);
        }

        if ($request->has('niveau_id') && $request->niveau_id != '') {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->has('has_echeancier') && $request->has_echeancier != '') {
            if ($request->has_echeancier == '1') {
                $query->whereNotNull('plan_echeancier_id');
            } else {
                $query->whereNull('plan_echeancier_id');
            }
        }

        if ($request->has('etat') && $request->etat != '') {
            $query->where('etat', $request->etat);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('montant', function($row) {
                return number_format($row->montant, 0, ',', ' ') . ' FCFA';
            })
            ->editColumn('type_paiement', function($row) {
                $labels = [
                    1 => 'Inscription',
                    2 => 'Scolarité',
                    3 => 'Services',
                    4 => 'Produit',
                    5 => 'Livre',
                    6 => 'Caution',
                    7 => 'Bus',
                    8 => 'Cantine',
                    9 => 'Autres',
                    10 => 'Assurance',
                    11 => 'Extra scolaire',
                    12 => 'Examen'
                ];
                $colors = [
                    1 => 'primary',
                    2 => 'info',
                    3 => 'success',
                    4 => 'warning',
                    5 => 'dark',
                    6 => 'secondary',
                    7 => 'info',
                    8 => 'success',
                    9 => 'secondary',
                    10 => 'primary',
                    11 => 'warning',
                    12 => 'danger'
                ];
                $label = $labels[$row->type_paiement] ?? 'Inconnu';
                $color = $colors[$row->type_paiement] ?? 'secondary';
                return '<span class="badge badge-' . $color . '">' . $label . '</span>';
            })
            ->editColumn('niveau_id', function($row) {
                return $row->niveau ? $row->niveau->libelle : '-';
            })
            ->editColumn('plan_echeancier_id', function($row) {
                if ($row->plan_echeancier_id) {
                    return '<span class="badge badge-info"><i class="fas fa-calendar-alt"></i> ' . ($row->planEcheancier ? $row->planEcheancier->nom : 'Plan') . '</span>';
                }
                return '<span class="badge badge-secondary">Non</span>';
            })
            ->editColumn('etat', function($row) {
                return $row->etat == 1
                    ? '<span class="badge badge-success">Actif</span>'
                    : '<span class="badge badge-danger">Inactif</span>';
            })
            ->addColumn('actions', function($row) {
                return '
                    <div class="dropdown">
                        <button class="btn action-dropdown-btn" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <button class="dropdown-item btn-detail" data-id="' . $row->id . '">
                                    <i class="fas fa-eye"></i> Détail
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item btn-edit" data-id="' . $row->id . '">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item btn-toggle-active" data-id="' . $row->id . '">
                                    <i class="fas ' . ($row->etat == 1 ? 'fa-pause' : 'fa-play') . '"></i>
                                    ' . ($row->etat == 1 ? 'Désactiver' : 'Activer') . '
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item text-danger btn-delete" data-id="' . $row->id . '">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </button>
                            </li>
                        </ul>
                    </div>
                ';
            })
            ->rawColumns(['type_paiement', 'plan_echeancier_id', 'etat', 'actions'])
            ->make(true);
    }

    /**
     * Afficher les détails d'un frais
     */
    public function show(FraisEcole $frais)
    {
        $frais->load(['niveau', 'annee', 'planEcheancier.lignes']);

        return response()->json([
            'success' => true,
            'data' => $frais,
            'type_paiement_label' => $frais->type_paiement_label,
            'can_delete' => $this->repository->canDelete($frais),
        ]);
    }

    /**
     * Enregistrer un nouveau frais
     */
    public function store(FraisEcoleRequest $request)
    {
        try {
            // Récupérer l'année en cours
            $anneeCourante = $this->authService->getCurrentYear();
            if (!$anneeCourante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active.'
                ], 400);
            }

            $fraisData = $request->validatedWithDefaults();
            $fraisData['annee_id'] = $anneeCourante->id;

            $planData = $request->input('plan_echeancier');

            $frais = $this->service->createFraisWithEcheancier($fraisData, $planData);

            return response()->json([
                'success' => true,
                'message' => 'Frais créé avec succès.',
                'data' => $frais->load(['planEcheancier.lignes'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du frais', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un frais
     */
    public function update(FraisEcoleRequest $request, FraisEcole $frais)
    {
        try {
            $fraisData = $request->validatedWithDefaults();
            $planData = $request->input('plan_echeancier');

            $frais = $this->service->updateFraisWithEcheancier($frais, $fraisData, $planData);

            return response()->json([
                'success' => true,
                'message' => 'Frais mis à jour avec succès.',
                'data' => $frais->load(['planEcheancier.lignes'])
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du frais', [
                'error' => $e->getMessage(),
                'frais_id' => $frais->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un frais
     */
    public function destroy(FraisEcole $frais)
    {
        try {
            if (!$this->repository->canDelete($frais)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce frais ne peut pas être supprimé car il est utilisé.'
                ], 422);
            }

            $this->service->deleteFraisWithEcheancier($frais);

            return response()->json([
                'success' => true,
                'message' => 'Frais supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du frais', [
                'error' => $e->getMessage(),
                'frais_id' => $frais->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un frais
     */
    public function toggleActive(FraisEcole $frais)
    {
        try {
            $frais = $this->repository->toggleActive($frais);

            return response()->json([
                'success' => true,
                'message' => $frais->etat === 1 ? 'Frais activé.' : 'Frais désactivé.',
                'data' => $frais
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du toggle du frais', [
                'error' => $e->getMessage(),
                'frais_id' => $frais->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques (API)
     */
    public function stats()
    {
        try {
            $stats = $this->repository->getStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les plans d'échéancier pour un frais
     */
    public function getPlans(Request $request)
    {
        try {
            $plans = $this->planRepository->getActivePlans();

            return response()->json([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
}
