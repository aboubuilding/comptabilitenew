<?php

namespace App\Http\Controllers\Domain\Administration;

use App\Domain\Administration\DTO\UserDTO;
use App\Domain\Administration\Exports\UsersExport;
use App\Domain\Administration\Repositories\UserRepository;
use App\Domain\Administration\Services\UserService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Concerns\LogsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Administration\StoreUserRequest;
use App\Domain\Administration\Models\Role;
use App\Domain\Administration\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Gestion des utilisateurs internes (Module 6.1).
 * Chaque action est journalisée.
 */
class UserController extends Controller
{
    use HandlesControllerErrors;
    use LogsActions;

    public function __construct(
        private readonly UserService $service,
        private readonly UserRepository $repository
    ) {
        $this->middleware('permission:utilisateurs.voir')->only(['index', 'export']);
        $this->middleware('permission:utilisateurs.gerer')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filtres = $request->only(['role_id', 'recherche']);
        $users   = $this->repository->listePaginee($filtres, 15);
        $roles   = Role::actif()->orderBy('niveau')->get();

        return view('domain.administration.users.index', compact('users', 'roles', 'filtres'));
    }

    public function create()
    {
        $roles = Role::actif()->orderBy('niveau')->get();

        return view('domain.administration.users._modal_form', [
            'user'   => null,
            'roles'  => $roles,
            'action' => route('administration.users.store'),
            'method' => 'POST',
            'titre'  => 'Nouvel utilisateur',
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $dto = UserDTO::fromArray($request->validated());
                $user = $this->service->creer($dto, $request->file('photo'));

                $this->logAction(
                    'create',
                    'User',
                    $user->id,
                    "Création de l'utilisateur « {$user->login} »",
                    ['login' => $user->login, 'role_id' => $user->role_id]
                );
            },
            'Utilisateur créé avec succès.'
        );
    }

    public function edit(User $user)
    {
        $roles = Role::actif()->orderBy('niveau')->get();

        return view('domain.administration.users._modal_form', [
            'user'   => $user,
            'roles'  => $roles,
            'action' => route('administration.users.update', $user),
            'method' => 'PUT',
            'titre'  => "Modifier l'utilisateur",
        ]);
    }

    public function update(StoreUserRequest $request, User $user)
    {
        return $this->runAjax(
            function () use ($request, $user) {
                $avant = $user->only(['nom', 'prenom', 'login', 'email', 'role_id', 'etat']);

                $dto = UserDTO::fromArray($request->validated());
                $this->service->mettreAJour($user, $dto, $request->file('photo'));

                $apres = $user->fresh()->only(['nom', 'prenom', 'login', 'email', 'role_id', 'etat']);

                $this->logAction(
                    'update',
                    'User',
                    $user->id,
                    "Modification de l'utilisateur « {$user->login} »",
                    ['avant' => $avant, 'apres' => $apres]
                );
            },
            'Utilisateur mis à jour avec succès.'
        );
    }

    public function destroy(User $user)
    {
        return $this->runAjax(
            function () use ($user) {
                $login = $user->login;
                $this->service->desactiver($user);

                $this->logAction(
                    'delete',
                    'User',
                    $user->id,
                    "Désactivation de l'utilisateur « {$login} »"
                );
            },
            'Utilisateur désactivé avec succès.'
        );
    }

    public function export(Request $request)
    {
        $filtres = $request->only(['role_id', 'recherche']);
        $users   = $this->repository->listePourExport($filtres);

        $this->logAction('export', 'User', null, 'Export Excel de la liste des utilisateurs', $filtres);

        return Excel::download(
            new UsersExport($users),
            'utilisateurs_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}