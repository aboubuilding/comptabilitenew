<?php

namespace App\Http\Controllers\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvenementRequest;
use App\Models\Evenement;
use App\Models\Annee;
use App\Repositories\Interfaces\EvenementRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class EvenementController extends Controller
{
    protected EvenementRepositoryInterface $repository;
    protected AuthService $authService;

    public function __construct(
        EvenementRepositoryInterface $repository,
        AuthService $authService
    ) {
        $this->repository = $repository;
        $this->authService = $authService;
    }

    /**
     * Liste des événements
     */
    public function index(Request $request)
    {
        $anneeCourante = $this->authService->getCurrentYear();

        $filters = [
            'search' => $request->get('search'),
            'type' => $request->get('type'),
            'statut' => $request->get('statut'),
            'annee_id' => $anneeCourante ? $anneeCourante->id : null,
            'etat' => $request->get('etat'),
        ];

        $evenements = $this->repository->getAllWithFilters($filters);
        $stats = $this->repository->getStats();
        $annees = Annee::active()->orderBy('libelle')->get();

        return view('admin.evenements.index', compact('evenements', 'stats', 'annees', 'anneeCourante'));
    }

    /**
     * Récupérer les données pour DataTables
     */
    public function getData(Request $request)
    {
        try {
            $anneeCourante = $this->authService->getCurrentYear();

            if (!$anneeCourante) {
                return response()->json(['error' => 'Aucune année scolaire active'], 400);
            }

            $query = Evenement::with('annee')
                ->where('annee_id', $anneeCourante->id);

            // Filtres
            if ($request->has('type') && $request->type != '') {
                $query->where('type', $request->type);
            }

            if ($request->has('statut') && $request->statut != '') {
                if ($request->statut === 'upcoming') {
                    $query->upcoming();
                } elseif ($request->statut === 'past') {
                    $query->past();
                }
            }

            if ($request->has('etat') && $request->etat != '') {
                $query->where('etat', $request->etat);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('type', function($row) {
                    return '<span class="badge ' . $row->type_badge_class . '">
                        <i class="fas ' . $row->type_icon . '"></i> ' . $row->type_label . '
                    </span>';
                })
                ->editColumn('date_evenement', function($row) {
                    return $row->date_evenement->format('d/m/Y');
                })
                ->editColumn('participation', function($row) {
                    return number_format($row->participation, 0, ',', ' ') . ' FCFA';
                })
                ->editColumn('capacite', function($row) {
                    return $row->capacite ?? 'Illimité';
                })
                ->editColumn('statut', function($row) {
                    if ($row->isPast()) {
                        return '<span class="badge badge-secondary">Passé</span>';
                    } elseif ($row->isToday()) {
                        return '<span class="badge badge-warning">Aujourd\'hui</span>';
                    } else {
                        return '<span class="badge badge-success">À venir</span>';
                    }
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
                ->rawColumns(['type', 'statut', 'etat', 'actions'])
                ->make(true);
        } catch (\Throwable $e) {
            // Avant : aucune capture ici -> toute exception (y compris l'erreur
            // "Class DataTables not found" causée par l'import manquant)
            // renvoyait la page d'erreur HTML de Laravel, que DataTables ne
            // pouvait pas parser en JSON -> "Ajax error" côté client.
            // Désormais on log et on renvoie toujours du JSON exploitable.
            Log::error('Erreur lors du chargement des données des événements (DataTables)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors du chargement des événements.',
            ], 500);
        }
    }

    /**
     * Afficher les détails d'un événement
     */
    public function show(Evenement $evenement)
    {
        $evenement->load('annee');

        return response()->json([
            'success' => true,
            'data' => $evenement,
            'type_label' => $evenement->type_label,
            'type_badge_class' => $evenement->type_badge_class,
            'type_icon' => $evenement->type_icon,
            'can_delete' => $this->repository->canDelete($evenement),
        ]);
    }

    /**
     * Enregistrer un nouvel événement
     */
    public function store(EvenementRequest $request)
    {
        try {
            $anneeCourante = $this->authService->getCurrentYear();
            if (!$anneeCourante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active.'
                ], 400);
            }

            $data = $request->validatedWithDefaults();
            $data['annee_id'] = $anneeCourante->id;

            $evenement = $this->repository->createWithValidation($data);

            return response()->json([
                'success' => true,
                'message' => 'Événement créé avec succès.',
                'data' => $evenement
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'événement', [
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
     * Mettre à jour un événement
     */
    public function update(EvenementRequest $request, Evenement $evenement)
    {
        try {
            $data = $request->validatedWithDefaults();
            $evenement = $this->repository->updateWithValidation($evenement, $data);

            return response()->json([
                'success' => true,
                'message' => 'Événement mis à jour avec succès.',
                'data' => $evenement
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'événement', [
                'error' => $e->getMessage(),
                'evenement_id' => $evenement->id,
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un événement
     */
    public function destroy(Evenement $evenement)
    {
        try {
            if (!$this->repository->canDelete($evenement)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet événement ne peut pas être supprimé car il a des participants.'
                ], 422);
            }

            $this->repository->delete($evenement->id);

            return response()->json([
                'success' => true,
                'message' => 'Événement supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'événement', [
                'error' => $e->getMessage(),
                'evenement_id' => $evenement->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un événement
     */
    public function toggleActive(Evenement $evenement)
    {
        try {
            $evenement = $this->repository->toggleActive($evenement);

            return response()->json([
                'success' => true,
                'message' => $evenement->etat === 1 ? 'Événement activé.' : 'Événement désactivé.',
                'data' => $evenement
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du toggle de l\'événement', [
                'error' => $e->getMessage(),
                'evenement_id' => $evenement->id
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
}
