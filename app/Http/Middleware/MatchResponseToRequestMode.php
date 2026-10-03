<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accorde la forme de la réponse à la façon dont l'action a été appelée.
 *
 * Les contrôleurs répondent soit en JSON (runAjax : {success, message}), soit
 * par une redirection avec message flash (runWeb). Les écrans, eux, appellent
 * ces actions tantôt en AJAX (fenêtres modales), tantôt par un formulaire
 * classique (menus d'actions, confirmation puis form.submit()). Quand les deux
 * ne s'accordent pas :
 *
 *  - formulaire classique → action JSON : l'utilisateur voyait le JSON brut
 *    en pleine page. On le ramène d'où il vient, message en notification.
 *
 *  - appel AJAX → action à redirection : le navigateur suivait la redirection
 *    en silence et le script, recevant une page HTML « 200 », annonçait un
 *    succès même lorsque le serveur avait refusé l'opération. On renvoie le
 *    message flash en JSON, avec un statut d'erreur si c'en est une.
 */
class MatchResponseToRequestMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethodSafe()) {
            return $response;
        }

        if ($response instanceof JsonResponse && ! $request->expectsJson()) {
            return $this->jsonVersRedirection($response);
        }

        if ($response instanceof RedirectResponse && $request->expectsJson() && $request->hasSession()) {
            return $this->redirectionVersJson($request, $response);
        }

        return $response;
    }

    private function jsonVersRedirection(JsonResponse $response): Response
    {
        $contenu = $response->getData(true);

        if (! is_array($contenu) || ! array_key_exists('message', $contenu)) {
            return $response;
        }

        $reussi  = $response->isSuccessful() && ($contenu['success'] ?? true) !== false;
        $message = (string) $contenu['message'];

        return $reussi
            ? redirect()->back()->with('success', $message)
            : redirect()->back()->withInput()->with('error', $message);
    }

    private function redirectionVersJson(Request $request, RedirectResponse $response): Response
    {
        $session = $request->session();

        if (! $session->has('success') && ! $session->has('error')) {
            return $response;
        }

        $erreur  = $session->get('error');
        $message = $erreur ?? $session->get('success');

        // Le message part dans la réponse JSON : il ne doit pas réapparaître
        // au prochain chargement de page.
        $session->forget(['success', 'error', '_old_input']);

        if ($erreur !== null) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'redirect' => $response->getTargetUrl(),
        ]);
    }
}
