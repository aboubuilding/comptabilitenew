<?php

/**
 * Écrit e2e/.cache/menu.json : pour chaque compte E2E, les entrées du menu
 * (config/menu.php) avec leur URL et le droit attendu d'après la matrice
 * (config/permissions.php). Les specs de navigation s'appuient dessus pour
 * vérifier, dans le navigateur, que l'écran correspond à la matrice.
 *
 * Lancé par e2e/global-setup.ts, avec l'environnement E2E.
 */

use App\Domain\Administration\Models\User;
use Database\Seeders\E2eSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sortie = [];

foreach (array_keys(E2eSeeder::COMPTES) as $login) {
    $utilisateur = User::where('login', $login)->firstOrFail();
    $entrees = [];

    foreach (config('menu', []) as $menu) {
        foreach ($menu['groupes'] as $groupe => $lignes) {
            foreach ($lignes as [$libelle, , $route, $permission]) {
                if (! Route::has($route)) {
                    continue;
                }

                $entrees[] = [
                    'menu'       => $menu['libelle'],
                    'groupe'     => $groupe,
                    'libelle'    => $libelle,
                    'route'      => $route,
                    'chemin'     => route($route, [], false),
                    'permission' => $permission,
                    'autorise'   => Gate::forUser($utilisateur)->allows($permission),
                ];
            }
        }
    }

    $sortie[$login] = $entrees;
}

$dossier = __DIR__ . '/../.cache';
if (! is_dir($dossier)) {
    mkdir($dossier, 0777, true);
}

file_put_contents(
    $dossier . '/menu.json',
    json_encode($sortie, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo count($sortie) . " profils exportés\n";
