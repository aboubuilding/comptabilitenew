<?php

namespace App\Http\Controllers\Concerns;

use App\Domain\Administration\Models\JournalAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Trait à utiliser dans les contrôleurs pour journaliser automatiquement
 * les actions sensibles (create / update / delete / export / login / logout...).
 *
 * Usage :
 *   $this->logAction('create', 'Assure', $assure->id, 'Souscription assurance créée', $dto->toArray());
 */
trait LogsActions
{
    /**
     * Enregistre une action dans le journal.
     *
     * @param  string       $action       create|update|delete|export|login|logout|...
     * @param  string|null  $entite       Nom court de l'entité (Assure, Employe...)
     * @param  int|null     $entiteId     Identifiant de l'entité concernée
     * @param  string|null  $description  Message lisible
     * @param  array|null   $payload      Données utiles (diff, filtres, ids...)
     */
    protected function logAction(
        string $action,
        ?string $entite = null,
        ?int $entiteId = null,
        ?string $description = null,
        ?array $payload = null
    ): void {
        try {
            /** @var Request|null $request */
            $request = request();
            $user    = Auth::user();

            // Module déduit du namespace du contrôleur (ex: Domain\Rh\... → Rh)
            $module = $this->deduireModule();

            JournalAction::create([
                'user_id'      => $user?->id,
                'user_nom'     => $user?->nom_complet,
                'user_login'   => $user?->login,
                'module'       => $module,
                'action'       => $action,
                'entite'       => $entite,
                'entite_id'    => $entiteId,
                'description'  => $description,
                'payload'      => $payload,
                'ip'           => $request?->ip(),
                'user_agent'   => substr((string) $request?->userAgent(), 0, 255),
                'route'        => $request?->route()?->getName(),
                'methode_http' => $request?->method(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // On ne doit jamais bloquer une action métier à cause du log
            \Log::warning('Journalisation échouée : ' . $e->getMessage());
        }
    }

    /**
     * Déduit le module à partir du namespace du contrôleur.
     */
    protected function deduireModule(): string
    {
        $classe = static::class;
        if (preg_match('/Domain\\\\([^\\\\]+)\\\\/', $classe, $m)) {
            return $m[1];
        }
        return 'App';
    }
}