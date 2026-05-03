<?php

namespace App\Repositories\Eloquent;

use App\Models\Mouvement;
use App\Models\Depense;
use App\Models\Paiement;
use App\Repositories\Interfaces\MouvementRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Types\TypeMouvement;
use App\Types\StatutMouvement;
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
        // Valeurs par défaut
        $data['reference']        = $data['reference'] ?? $this->genererReference();
        $data['statut_mouvement'] = $data['statut_mouvement'] ?? StatutMouvement::ENREGISTRER;
        $data['date_mouvement']   = $data['date_mouvement'] ?? now();
        $data['etat']             = $data['etat'] ?? BaseRepository::ACTIF;
        $data['motif']            = $data['motif'] ?? '';

        // Empêcher le couplage simultané Dépense + Paiement
        if (!empty($data['depense_id']) && !empty($data['paiement_id'])) {
            throw new InvalidArgumentException('Un mouvement ne peut pas être lié simultanément à une dépense et à un paiement.');
        }

        // 🔄 Cas 1 : Mouvement initié par une DÉPENSE (Décaissement)
        if (!empty($data['depense_id'])) {
            $depense = Depense::find($data['depense_id']);
            if (!$depense) {
                throw new InvalidArgumentException("La dépense ID {$data['depense_id']} est introuvable.");
            }
            
            $data['depense_id']     = $depense->id;
            $data['motif']          = $depense->motif_depense;
            $data['type_mouvement'] = $data['type_mouvement'] ?? TypeMouvement::DECAISSEMENT;
        } 
        // 🔄 Cas 2 : Mouvement initié par un PAIEMENT (Encaissement)
        elseif (!empty($data['paiement_id'])) {
            $paiement = Paiement::find($data['paiement_id']);
            if (!$paiement) {
                throw new InvalidArgumentException("Le paiement ID {$data['paiement_id']} est introuvable.");
            }
            
            $data['paiement_id']    = $paiement->id;
            $data['motif']          = $paiement->motif_paiement;
            $data['type_mouvement'] = $data['type_mouvement'] ?? TypeMouvement::ENCAISSEMENT;
        }

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

        $required = ['caisse_id', 'utilisateur_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Le champ {$field} est obligatoire.");
            }
        }
    }
}