<?php

namespace App\Http\Controllers\Domain;

use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Models\Espace;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Recherche globale (fenêtre Ctrl+K de l'en-tête).
 *
 * Renvoie un tableau d'objets { title, subtitle, icon, category, url } :
 *   - les écrans du menu (config/menu.php) ;
 *   - les élèves, par nom, prénom ou matricule ;
 *   - les familles, par nom.
 * Chaque source n'est interrogée que si l'utilisateur a le droit de la
 * consulter : la recherche ne révèle rien de plus que le menu.
 */
class RechercheController extends Controller
{
    private const MAX_PAR_SOURCE = 6;

    public function __invoke(Request $request): JsonResponse
    {
        $terme = trim((string) $request->query('q', ''));

        if (mb_strlen($terme) < 2) {
            return response()->json([]);
        }

        $utilisateur = $request->user();
        $resultats   = $this->ecrans($terme, $utilisateur);

        if ($utilisateur->can('eleves.voir')) {
            $resultats = array_merge($resultats, $this->eleves($terme));
        }

        if ($utilisateur->can('familles.voir')) {
            $resultats = array_merge($resultats, $this->familles($terme));
        }

        return response()->json($resultats);
    }

    private function ecrans(string $terme, $utilisateur): array
    {
        $aiguille  = Str::lower(Str::ascii($terme));
        $resultats = [];

        foreach (config('menu', []) as $menu) {
            foreach ($menu['groupes'] as $groupe => $entrees) {
                foreach ($entrees as [$libelle, $icone, $route, $permission]) {
                    $botte = Str::lower(Str::ascii("{$libelle} {$groupe}"));

                    if (! str_contains($botte, $aiguille) || ! Route::has($route) || ! $utilisateur->can($permission)) {
                        continue;
                    }

                    $resultats[] = [
                        'title'    => $libelle,
                        'subtitle' => "{$menu['libelle']} › {$groupe}",
                        'icon'     => $icone,
                        'category' => 'Écran',
                        'url'      => route($route),
                    ];
                }
            }
        }

        return array_slice($resultats, 0, self::MAX_PAR_SOURCE);
    }

    private function eleves(string $terme): array
    {
        return Eleve::query()
            ->where('etat', 1)
            ->where(function ($q) use ($terme) {
                $q->where('nom', 'like', "%{$terme}%")
                    ->orWhere('prenom', 'like', "%{$terme}%")
                    ->orWhere('matricule', 'like', "%{$terme}%");
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->limit(self::MAX_PAR_SOURCE)
            ->get(['id', 'nom', 'prenom', 'matricule'])
            ->map(fn (Eleve $eleve) => [
                'title'    => trim("{$eleve->nom} {$eleve->prenom}"),
                'subtitle' => $eleve->matricule ? "Matricule {$eleve->matricule}" : 'Élève',
                'icon'     => 'fa-user-graduate',
                'category' => 'Élève',
                'url'      => route('scolarite.eleves.show', $eleve->id),
            ])
            ->all();
    }

    private function familles(string $terme): array
    {
        return Espace::query()
            ->where('etat', 1)
            ->where('nom_famille', 'like', "%{$terme}%")
            ->orderBy('nom_famille')
            ->limit(self::MAX_PAR_SOURCE)
            ->get(['id', 'nom_famille'])
            ->map(fn (Espace $espace) => [
                'title'    => $espace->nom_famille,
                'subtitle' => 'Espace familial',
                'icon'     => 'fa-user-friends',
                'category' => 'Famille',
                'url'      => route('scolarite.espaces.show', $espace->id),
            ])
            ->all();
    }
}
