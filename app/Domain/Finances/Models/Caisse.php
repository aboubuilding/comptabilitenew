<?php

namespace App\Domain\Finances\Models;

use App\Domain\Administration\Models\User;
use App\Domain\Finances\Types\CaisseStatut;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caisse extends Model
{
    protected $table = 'caisses';

    protected $fillable = [
        'libelle',
        'solde_initial', 'solde_final', 'solde_compte',
        'ecart', 'motif_ecart',
        'date_ouverture', 'date_cloture',
        'statut',
        'User_id', 'responsable_id', 'annee_id',
        'valide_par', 'date_validation_ecart',
        'etat',
    ];

    protected $casts = [
        'statut'                => CaisseStatut::class,
        'solde_initial'         => 'float',
        'solde_final'           => 'float',
        'solde_compte'          => 'float',
        'ecart'                 => 'float',
        'date_ouverture'        => 'datetime',
        'date_cloture'          => 'datetime',
        'date_validation_ecart' => 'datetime',
    ];

    /* ===================== Relations ===================== */

    public function caissier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_id');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Mouvement::class, 'caisse_id');
    }

    /* ===================== Helpers ===================== */

    public function isOuverte(): bool
    {
        return $this->statut === CaisseStatut::OUVERTE;
    }

    /* =========================================================
       Calculs dérivés — TOUJOURS calculés à la volée tant que
       la caisse est ouverte. À la clôture, ces valeurs sont
       figées dans les colonnes solde_final / ecart.
       ========================================================= */

    /** Σ crédits (encaissements + entrées manuelles). */
    public function getTotalEntreesAttribute(): float
    {
        return round(
            $this->mouvements
                ->filter(fn (Mouvement $m) => $m->type_mouvement?->isEntree())
                ->sum('montant'),
            2
        );
    }

    /** Σ débits (dépenses + sorties manuelles). */
    public function getTotalSortiesAttribute(): float
    {
        return round(
            $this->mouvements
                ->filter(fn (Mouvement $m) => $m->type_mouvement && ! $m->type_mouvement->isEntree())
                ->sum('montant'),
            2
        );
    }

    /** Solde théorique courant = solde_initial + entrées − sorties. */
    public function getSoldeTheoriqueAttribute(): float
    {
        return round(
            (float) ($this->solde_initial ?? 0)
            + $this->total_entrees
            - $this->total_sorties,
            2
        );
    }
}