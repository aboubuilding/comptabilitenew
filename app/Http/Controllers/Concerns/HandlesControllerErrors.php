<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Centralise la gestion d'erreurs des actions d'écriture (create/update/
 * delete) pour éviter de dupliquer le même try/catch dans chacun des 13
 * modules. Deux variantes :
 * - runAjax()  : pour les Controllers qui répondent en JSON (ex. la
 *                modale de Cycle)
 * - runWeb()   : pour les Controllers en pages séparées + redirection
 *                classique (ex. Niveau)
 *
 * Ce que Laravel gère déjà tout seul et qu'on ne duplique PAS ici :
 * modèle introuvable (ModelNotFoundException), validation échouée
 * (ValidationException), non authentifié/non autorisé — le handler
 * d'exceptions par défaut les convertit déjà proprement en JSON dès que
 * la requête attend du JSON (Accept: application/json, envoyé par défaut
 * par $.ajax/$.getJSON).
 *
 * Ce qui reste à la charge du Controller : les erreurs SQL (contrainte
 * de clé étrangère, doublon en cas de double-soumission malgré la
 * validation) et tout imprévu — sans jamais exposer le message SQL brut
 * à l'utilisateur.
 */
trait HandlesControllerErrors
{
    /**
     * @param  \Closure $action  Doit retourner void, ou un tableau à
     *                           fusionner dans la réponse JSON de succès
     *                           (ex. ['cycle' => $cycle]).
     */
    protected function runAjax(\Closure $action, string $successMessage): JsonResponse
    {
        try {
            $result = $action();

            return response()->json(array_merge(
                ['success' => true, 'message' => $successMessage],
                is_array($result) ? $result : []
            ));
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => $this->logAndDescribeQueryException($e),
            ], 500);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $this->logAndDescribeUnexpectedException($e),
            ], 500);
        }
    }

    protected function runWeb(
    \Closure $action,
    string $redirectRoute,
    string $successMessage,
    array|\Closure $routeParams = [],
): RedirectResponse {
    try {
        $action();
        $params = $routeParams instanceof \Closure ? $routeParams() : $routeParams;

        return redirect()->route($redirectRoute, $params)->with('success', $successMessage);
    } catch (QueryException $e) {
        return back()->withInput()->with('error', $this->logAndDescribeQueryException($e));
    } catch (Throwable $e) {
        return back()->withInput()->with('error', $this->logAndDescribeUnexpectedException($e));
    }
}

    private function logAndDescribeQueryException(QueryException $e): string
    {
        Log::error('Erreur base de données', [
            'controller' => static::class,
            'exception'  => $e->getMessage(),
            'sql'        => $e->getSql(),
        ]);

        // Heuristique simple : distingue une contrainte de clé étrangère
        // (élément encore référencé ailleurs) d'un doublon ou d'une autre
        // erreur SQL, sans jamais renvoyer le message brut au client.
        if (str_contains(strtolower($e->getMessage()), 'foreign key')) {
            return 'Impossible : cet élément est encore utilisé ailleurs dans l\'application.';
        }

        return "Une erreur est survenue lors de l'enregistrement. Vérifiez les données saisies.";
    }

    private function logAndDescribeUnexpectedException(Throwable $e): string
    {
        Log::error('Erreur inattendue', [
            'controller' => static::class,
            'exception'  => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);

        return 'Une erreur technique est survenue. Veuillez réessayer ou contacter le support.';
    }
}