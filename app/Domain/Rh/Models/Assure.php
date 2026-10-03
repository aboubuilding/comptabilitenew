<?php

namespace App\Domain\Rh\Models;

use App\Domain\Rh\Models\Asurance;
use App\Domain\Rh\Models\Employe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Souscription d'un employé à une assurance.
 * Table : assures
 */
class Assure extends Model
{
    protected $table = 'assures';

    protected $fillable = [
        'employe_id',
        'personnel_id', // conservé pour compatibilité migration d'origine
        'assurance_id',
        'date_souscription',
        'prelevement_mensuel',
        'statut',
        'annee_id',
        'etat',
    ];

    protected $casts = [
        'date_souscription'   => 'date',
        'prelevement_mensuel' => 'decimal:2',
        'annee_id'            => 'integer',
        'etat'                => 'integer',
    ];

    /**
     * Employé assuré.
     */
    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }

    /**
     * Assurance (assureur / police).
     */
    public function assurance(): BelongsTo
    {
        return $this->belongsTo(Asurance::class, 'assurance_id');
    }

    /**
     * Scope actifs.
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}