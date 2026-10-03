<?php

namespace App\Http\Controllers\Domain\Administration;

use App\Domain\Administration\Exports\JournalExport;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Domain\Administration\Models\JournalAction;
use App\Domain\Administration\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Écran Journal & Traçabilité (Module 6.4).
 * Consultable par l'Administrateur.
 */
class JournalController extends Controller
{
    use HandlesControllerErrors;

    public function __construct()
    {
        $this->middleware('permission:journal.voir')->only(['index', 'export']);
    }

    public function index(Request $request)
    {
        $filtres = $request->only(['user_id', 'module', 'action', 'date_debut', 'date_fin', 'recherche']);

        $actions  = $this->requeteFiltree($filtres)->paginate(25)->withQueryString();
        $users    = User::orderBy('nom')->get(['id', 'nom', 'prenom', 'login']);
        $modules  = JournalAction::distinct()->pluck('module')->filter()->sort()->values();
        $types    = JournalAction::distinct()->pluck('action')->filter()->sort()->values();

        return view('domain.administration.journal.index', compact(
            'actions', 'users', 'modules', 'types', 'filtres'
        ));
    }

    /** Requête du journal avec les filtres de l'écran, partagée par la liste et l'export. */
    private function requeteFiltree(array $filtres)
    {
        $query = JournalAction::with('user')->latest('created_at');

        if (!empty($filtres['user_id'])) {
            $query->where('user_id', $filtres['user_id']);
        }
        if (!empty($filtres['module'])) {
            $query->where('module', $filtres['module']);
        }
        if (!empty($filtres['action'])) {
            $query->where('action', $filtres['action']);
        }
        if (!empty($filtres['date_debut'])) {
            $query->whereDate('created_at', '>=', $filtres['date_debut']);
        }
        if (!empty($filtres['date_fin'])) {
            $query->whereDate('created_at', '<=', $filtres['date_fin']);
        }
        if (!empty($filtres['recherche'])) {
            $r = $filtres['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('description', 'like', "%{$r}%")
                  ->orWhere('entite', 'like', "%{$r}%")
                  ->orWhere('user_nom', 'like', "%{$r}%")
                  ->orWhere('user_login', 'like', "%{$r}%");
            });
        }

        return $query;
    }

    public function export(Request $request)
    {
        $filtres = $request->only(['user_id', 'module', 'action', 'date_debut', 'date_fin', 'recherche']);

        $actions = $this->requeteFiltree($filtres)->limit(10000)->get();

        return Excel::download(
            new JournalExport($actions),
            'journal_actions_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}