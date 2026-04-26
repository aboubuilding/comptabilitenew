<?php

namespace App\Repositories\Eloquent;

use App\Models\Mouvement;
use App\Repositories\Contracts\MouvementRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Constants\TypeMouvement;
use App\Constants\StatutMouvement;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class MouvementRepository extends BaseRepository implements MouvementRepositoryInterface
{
    public function __construct(Mouvement $model)
    {
        parent::__construct($model);
    }

    public function enregistrer(array $data): Model
    {
        $data['reference']        = $data['reference'] ?? $this->genererReference();
        $data['statut_mouvement'] = $data['statut_mouvement'] ?? StatutMouvement::ENREGISTRER;
        $data['etat']             = $data['etat'] ?? BaseRepository::ACTIF;
        $data['date_mouvement']   = $data['date_mouvement'] ?? now();

        $this->validateData($data);

        return DB::transaction(fn() => parent::create($data));
    }

    public function valider(int $id): bool
    {
        $mouvement = $this->findOrFail($id);

        if ($mouvement->statut_mouvement !== StatutMouvement::ENREGISTRER) {
            throw new LogicException('Seuls les mouvements en état "Enregistré" peuvent être validés.');
        }

        return $mouvement->update(['statut_mouvement' => StatutMouvement::VALIDER]);
    }

    public function rejeter(int $id, ?string $motif = null): bool
    {
        $mouvement = $this->findOrFail($id);

        if ($mouvement->statut_mouvement !== StatutMouvement::ENREGISTRER) {
            throw new LogicException('Seuls les mouvements en attente peuvent être rejetés.');
        }

        return $mouvement->update([
            'statut_mouvement' => StatutMouvement::REJETER,
            'observation'      => $motif ?? $mouvement->observation,
        ]);
    }

    public function getSoldeCaisse(int $caisseId, float $soldeInitial = 0): float
    {
        $result = $this->activeQuery()
            ->where('caisse_id', $caisseId)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) as entrees,
                COALESCE(SUM(CASE WHEN type_mouvement = ? THEN montant ELSE 0 END), 0) as sorties
            ', [TypeMouvement::ENCAISSEMENT, TypeMouvement::DECAISSEMENT])
            ->first();

        return (float) ($soldeInitial + ($result->entrees ?? 0) - ($result->sorties ?? 0));
    }

    public function getSoldeByType(int $caisseId, int $type): float
    {
        return (float) $this->activeQuery()
            ->where('caisse_id', $caisseId)
            ->where('type_mouvement', $type)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->sum('montant');
    }

    public function genererReference(): string
    {
        return 'MVT-' . now()->format('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public function getMouvementsByStatut(int $caisseId, int $statut): Collection
    {
        return $this->activeQuery()
            ->where('caisse_id', $caisseId)
            ->where('statut_mouvement', $statut)
            ->orderByDesc('date_mouvement')
            ->get();
    }

    /**
     * Validation interne des données requises
     */
    private function validateData(array $data): void
    {
        if (!isset($data['montant']) || !is_numeric($data['montant']) || $data['montant'] <= 0) {
            throw new InvalidArgumentException('Le montant doit être un nombre strictement positif.');
        }

        $type = (int) ($data['type_mouvement'] ?? 0);
        if (!in_array($type, [TypeMouvement::ENCAISSEMENT, TypeMouvement::DECAISSEMENT], true)) {
            throw new InvalidArgumentException('Type de mouvement invalide.');
        }

        $required = ['caisse_id', 'motif', 'utilisateur_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Le champ {$field} est obligatoire.");
            }
        }
    }
}