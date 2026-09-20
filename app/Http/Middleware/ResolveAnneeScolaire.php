<?php

namespace App\Http\Middleware;

use App\Domain\Scolarite\Repositories\AnneeRepository;
use App\Domain\Scolarite\Services\AnneeService;
use App\Support\AnneeScolaireContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout l'année scolaire active pour CETTE requête et alimente
 * AnneeScolaireContext, avant que Controllers/Repositories/Vues n'en
 * aient besoin. Ordre de résolution :
 *   1. ?annee_id= dans l'URL (le sélecteur du header force le changement)
 *   2. session (année mémorisée depuis une requête précédente)
 *   3. année active par défaut (AnneeService, ouverte sinon la plus
 *      récente, créée si aucune n'existe)
 *
 * C'est ICI que le changement d'année s'opère réellement. HeaderComposer
 * ne fait qu'afficher le résultat de ce middleware, il ne décide jamais
 * de l'année active.
 *
 * A appliquer sur tous les fichiers de routes/admin/, PAS globalement :
 * routes/auth.php (login) n'a pas besoin de contexte année.
 */
class ResolveAnneeScolaire
{
    public function __construct(
        private AnneeRepository $annees,
        private AnneeService $anneeService,
        private AnneeScolaireContext $context,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $anneeId = $this->resolve($request);

        $this->context->set($anneeId);
        session(['annee_id' => $anneeId]);

        return $next($request);
    }

    private function resolve(Request $request): int
    {
        // 1. Le sélecteur du header force le changement via ?annee_id=.
        // find() passe par activeQuery() : une année inactive/inconnue
        // ne peut pas être imposée par un id trafiqué dans l'URL.
        $requested = $request->integer('annee_id');
        if ($requested && $this->annees->find($requested)) {
            return $requested;
        }

        // 2. Année déjà choisie lors d'une requête précédente.
        $sessionId = (int) session('annee_id', 0);
        if ($sessionId && $this->annees->find($sessionId)) {
            return $sessionId;
        }

        // 3. Repli sur l'année active par défaut.
        return $this->anneeService->resolveActive()->id;
    }
}