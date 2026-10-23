<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\TypePrevision;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ligne budgétaire : un libellé et un montant global sur l'année ; les dépenses
 * qui lui sont imputées consomment ce montant.
 */
class Prevision extends Model
{
    protected $table = 'previsions';

    protected $fillable = [
        'libelle', 'type', 'montant_previsionnel', 'montant_realise',
        'centre_analytique_id', 'date_debut', 'date_fin',
        'annee_id', 'cree_par', 'etat',
    ];

    protected $casts = [
        'type'                => TypePrevision::class,
        'montant_previsionnel' => 'float',
        'montant_realise'     => 'float',
        'date_debut'          => 'date',
        'date_fin'            => 'date',
    ];

    public function centre(): BelongsTo
    {
        return $this->belongsTo(CentreAnalytique::class, 'centre_analytique_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    /** Dépenses imputées à la ligne. */
    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class, 'ligne_budgetaire_id');
    }

    public function ecart(): float
    {
        return round((float) $this->montant_previsionnel - (float) ($this->montant_realise ?? 0), 2);
    }
}