<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trait de gestion d'erreurs pour les contrôleurs.
 *
 * runAjax() et runWeb() acceptent désormais :
 *  - un message statique (string)            → « Opération réussie. »
 *  - un message dynamique (Closure)          → fn ($resultat) => "…"
 *
 * La Closure reçoit en paramètre la valeur retournée par l'action.
 * Elle peut donc produire un message contextuel (ex. nb d'éléments affectés).
 */
trait HandlesControllerErrors
{
    /**
     * Exécute une action et renvoie une réponse JSON (AJAX).
     *
     * @param  Closure              $action   Logique métier (peut retourner une valeur).
     * @param  string|Closure       $message  Message statique OU Closure recevant le résultat.
     * @param  int                  $status   Code HTTP en cas de succès (défaut 200).
     */
    protected function runAjax(Closure $action, string|Closure $message, int $status = 200): JsonResponse
    {
        try {
            $resultat = $action();

            $texte = $message instanceof Closure
                ? $message($resultat)
                : $message;

            return response()->json([
                'success' => true,
                'message' => $texte,
                'data'    => $resultat,
            ], $status);

        } catch (Throwable $e) {
            $this->journaliserEchec('AJAX', $e);

            return response()->json([
                'success' => false,
                'message' => $this->messageErreur($e),
                'errors'  => $this->erreursValidation($e),
            ], $this->codeErreur($e));
        }
    }

    /**
     * Exécute une action et renvoie une redirection (web classique).
     *
     * @param  Closure              $action
     * @param  string               $route    Nom de route cible.
     * @param  string|Closure       $message  Message statique OU Closure recevant le résultat.
     * @param  array|Closure        $params   Paramètres de route (array ou Closure).
     */
    protected function runWeb(
        Closure $action,
        string $route,
        string|Closure $message,
        array|Closure $params = []
    ): RedirectResponse {
        try {
            $resultat = $action();

            $texte  = $message instanceof Closure ? $message($resultat) : $message;
            $params = $params instanceof Closure ? $params($resultat) : $params;

            return redirect()
                ->route($route, $params)
                ->with('success', $texte);

        } catch (Throwable $e) {
            $this->journaliserEchec('WEB', $e);

            return back()
                ->withInput()
                ->with('error', $this->messageErreur($e));
        }
    }

    /**
     * Une règle de gestion refusée (ou une saisie invalide) est un événement
     * normal : simple avertissement. Seules les pannes sont des erreurs,
     * journalisées avec leur trace.
     */
    protected function journaliserEchec(string $mode, Throwable $e): void
    {
        $contexte = [
            'route'   => request()->route()?->getName(),
            'user_id' => auth()->id(),
        ];

        if ($this->estErreurMetier($e) || $e instanceof \Illuminate\Validation\ValidationException) {
            Log::warning("Action refusée ({$mode}) : " . $e->getMessage(), $contexte);

            return;
        }

        Log::error("Erreur contrôleur ({$mode}) : " . $e->getMessage(), $contexte + ['exception' => $e]);
    }

    /**
     * Message d'erreur lisible.
     */
    protected function messageErreur(Throwable $e): string
    {
        // En production, on masque les détails techniques, pas les règles de gestion
        if (app()->environment('production') && ! $this->estErreurMetier($e)) {
            return "Une erreur est survenue. Merci de réessayer ou de contacter l'administrateur.";
        }

        return $e->getMessage();
    }

    /**
     * Code HTTP adapté au type d'exception.
     */
    protected function codeErreur(Throwable $e): int
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return 422;
        }
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 404;
        }
        if ($this->estErreurMetier($e)) {
            return 422;
        }

        return 500;
    }

    /**
     * Règle de gestion refusée par un service (« Stock insuffisant », « Caisse
     * déjà clôturée »...), par opposition à une panne technique.
     */
    protected function estErreurMetier(Throwable $e): bool
    {
        return ($e instanceof \RuntimeException || $e instanceof \DomainException || $e instanceof \InvalidArgumentException)
            && ! $e instanceof \PDOException
            && ! $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            && ! $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException;
    }

    /**
     * Erreurs de validation structurées (pour le front).
     */
    protected function erreursValidation(Throwable $e): ?array
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $e->errors();
        }
        return null;
    }
}